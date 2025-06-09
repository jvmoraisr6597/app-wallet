import yfinance as yf
import redis
import sys
import json

r = redis.Redis(host='redis', port=6379, db=0)

def format_text_in_json(fields):
    if "JCP" in fields[0]:
        val = float(fields[3].replace(",", "."))
        fields[3] = val - ((val * 15) / 100)
    return fields

def extract_dividends(tickers):
    all_content = {}

    for ticker in tickers:
        stock = yf.Ticker(ticker)
        dividends = stock.dividends

        content = {}

        for date, value in dividends.items():
            formatted_date = date.strftime('%Y-%m-%d')
            value = round(value, 2)
            formatted_data = ["Dividendo", formatted_date, value]
            formatted_data = format_text_in_json(formatted_data)
            content[formatted_date] = formatted_data

        all_content[ticker] = content

    return all_content

# Recebe os tickers por argumento
tickers = sys.argv[1:]

# Executa a coleta e salva no Redis
all_dividends = extract_dividends(tickers)
print(all_dividends)
r.set("dividends", json.dumps(all_dividends))

# Imprime para fins de log
print("Dividendos salvos no Redis com sucesso.")
