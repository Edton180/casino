
import os
import openai

# Defina sua chave de API do OpenAI aqui (ou carregue do ambiente se preferir)
openai.api_key = os.getenv("OPENAI_API_KEY") or "SUA_CHAVE_AQUI"

# Caminho para o arquivo que você quer analisar (ajuste conforme necessário)
file_path = "app/Http/Controllers/PaymentController.php"

# Verifica se o arquivo existe
if not os.path.exists(file_path):
    print(f"Arquivo não encontrado: {file_path}")
    exit(1)

# Lê o conteúdo do arquivo PHP
with open(file_path, "r", encoding="utf-8") as f:
    file_content = f.read()

# Cria o prompt para análise
prompt = """
Você é um especialista em Laravel e integrações de pagamentos como Asaas, Gerencianet e Mercado Pago.
Analise o seguinte código PHP e sugira melhorias, detecte bugs e ajude a migrar a integração atual para a API Pix do Asaas.

Código:
""" + file_content

# Faz a requisição para a OpenAI
try:
    response = openai.ChatCompletion.create(
        model="gpt-4",
        messages=[
            {"role": "system", "content": "Você é um especialista em Laravel, PHP e APIs de pagamento."},
            {"role": "user", "content": prompt}
        ],
        temperature=0.5,
        max_tokens=2000,
    )
    print("Resposta da IA:\n")
    print(response["choices"][0]["message"]["content"])
except Exception as e:
    print(f"Erro ao chamar a OpenAI: {e}")
