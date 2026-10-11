<?php

namespace App\Install;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InstallerController
{
    public function show()
    {
        require_once __DIR__ . '/helpers.php';

        $detector = new EnvironmentDetector();
        $service = new InstallerService();
        $detected = $detector->detect();
        $requirements = $service->validateRequirements($detected);
        $installed = $service->isInstalled();

        if ($installed) {
            return response('Instalação já concluída. O instalador está bloqueado por segurança.', 423);
        }

        ob_start();
        $viewFile = __DIR__ . '/views/wizard.php';
        require $viewFile;

        return response(ob_get_clean());
    }

    public function testDatabase(Request $request)
    {
        require_once __DIR__ . '/helpers.php';

        $validator = Validator::make($request->all(), [
            'driver' => ['nullable', 'in:mysql,pgsql'],
            'host' => ['required', 'string'],
            'port' => ['required', 'numeric'],
            'database' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $service = new InstallerService();

        return response()->json($service->testDatabaseConnection($validator->validated()));
    }

    public function run(Request $request)
    {
        require_once __DIR__ . '/helpers.php';

        $validator = Validator::make($request->all(), [
            'install_mode' => ['required', 'in:vps,cpanel,localhost,docker'],
            'domain' => ['required', 'string', 'max:255'],
            'ssl_enabled' => ['required', 'boolean'],
            'app_name' => ['required', 'string', 'max:120'],
            'app_env' => ['required', 'in:local,production,staging'],
            'app_debug' => ['required', 'in:true,false'],
            'app_url' => ['required', 'url'],
            'timezone' => ['required', 'string', 'max:120'],
            'db_connection' => ['required', 'in:mysql,pgsql'],
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $detector = new EnvironmentDetector();
        $detected = $detector->detect();

        $service = new InstallerService();
        $result = $service->run($validator->validated(), $detected);

        if (! $result['ok']) {
            return back()->with('installer_error', $result['message'])->withInput();
        }

        return redirect('/')->with('installer_success', 'Instalação concluída. Faça login com: ' . $result['user_email']);
    }
}
