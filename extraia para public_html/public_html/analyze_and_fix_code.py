
import os
import sys
import openai

# Defina sua chave de API OpenAI (caso use OpenAI localmente configurado)
openai.api_key = os.getenv("OPENAI_API_KEY") or "SUA_CHAVE_AQUI"

def load_files(directory, max_size_mb=5):
    files = []
    for root, dirs, filenames in os.walk(directory):
        for filename in filenames:
            if filename.endswith(('.php', '.html', '.js')):
                path = os.path.join(root, filename)
                # pular arquivos muito grandes
                if os.path.getsize(path) > max_size_mb * 1024 * 1024:
                    print(f"Pulando {path} (tamanho > {max_size_mb}MB)")
                    continue
                try:
                    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                        content = f.read()
                    files.append({'path': path, 'content': content})
                except Exception as e:
                    print(f"Erro ao ler {path}: {e}")
    return files

def analyze_code_with_ai(code):
    prompt = f"Analise e corrija o seguinte código:\n\n{code}\n\nCorreções sugeridas:"
    resp = openai.Completion.create(model="text-davinci-003", prompt=prompt, max_tokens=2000)
    return resp['choices'][0]['text'].strip()

def fix_files(directory):
    files = load_files(directory)
    for f in files:
        print(f"Analisando {f['path']}...")
        corrected = analyze_code_with_ai(f['content'])
        with open(f['path'], 'w', encoding='utf-8') as w:
            w.write(corrected)
        print(f"Corrigido {f['path']}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python analyze_and_fix_code.py <diretorio_do_projeto>")
        sys.exit(1)
    fix_files(sys.argv[1])
