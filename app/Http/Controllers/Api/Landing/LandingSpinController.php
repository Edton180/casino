<?php

namespace App\Http\Controllers\Api\Landing {

    use App\Http\Controllers\Controller;
    use App\Models\SpinRuns;
    use Illuminate\Support\Str;
    use App\Helpers\Core as Helper;

    class LandingSpinController extends Controller
    {
        private $prizes;

        /**
         * Construct
         */
        function __construct()
        {
            $this->prizes = Helper::WheelPrizes();
        }

        /**
         * Spin
         * @return \Illuminate\Http\JsonResponse
         */
        public function Spin()
        {
            if (empty($this->prizes)) {
                return response()->json(['status' => false, 'error' => 'Roleta não configurada.'], 400);
            }

            $indexPrize = array_rand($this->prizes);
            $data = [
                'index'	=> $indexPrize,
                'prize'		=> $this->prizes[$indexPrize]
            ];
            $nonce = Str::uuid()->toString();
            $str = json_encode($data);

            $digest = hash_hmac('sha256', $str.$nonce, config('app.key'));

            SpinRuns::create([
                'key'	=> $digest,
                'possibilities'	=> json_encode($this->prizes),
                'nonce'	=> $nonce,
                'prize'	=> json_encode($data['prize'])
            ]);

            return response()->json([
                'status'	=> TRUE,
                'data'		=> $data,
                'signature'	=> $digest,
                'nonce'		=> $nonce
            ], 200);
        }

        public function Winners() {
            if (empty($this->prizes)) {
                return response()->json([[], 0]);
            }

            $levels = ['SUPER SPIN', 'LUCKY SPIN'];
            $currencies = [
                'BRL'
            ];
            $winners = [];
            $totalBonus = 9876543.21;

            for ($i = 0; $i < 200; $i++) {
                array_push($winners, [
                    'name'	=> $this->randomName(),
                    'nivel'	=> $levels[rand(0, count($levels) - 1)],
                    'prize'	=> $this->prizes[array_rand($this->prizes)]->value,
                    'currency'	=> $currencies[0]
                ]);
            }

            return response()->json([$winners, $totalBonus]);
        }

        /**
         * Nome fictício para a lista de ganhadores (o Faker não é instalado em produção).
         */
        private function randomName(): string
        {
            $first = ['Ana', 'Bruno', 'Carla', 'Diego', 'Eduarda', 'Felipe', 'Gabriela', 'Henrique', 'Isabela', 'João',
                'Larissa', 'Lucas', 'Mariana', 'Mateus', 'Natália', 'Pedro', 'Rafaela', 'Rodrigo', 'Sofia', 'Thiago'];
            $last  = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Lima', 'Pereira', 'Costa', 'Ferreira', 'Almeida', 'Ribeiro'];

            return $first[array_rand($first)] . ' ' . $last[array_rand($last)];
        }

        public function Prizes() {
            return response()->json(
                Helper::WheelPrizes()
            );
        }
    }
}
