#!/var/www/env/bin/python3
import yfinance as yf
import redis
import sys
import json

# Conectando ao Redis (use 'redis' se estiver em container, ou 'localhost' fora)
r = redis.Redis(host='127.0.0.1', port=6379, db=0)

def format_text_in_json(fields):
    if "JCP" in fields["type"]:
        val = fields["value"]
        fields["value"] = round(val - ((val * 15) / 100), 2)
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

            formatted_data = {
                "type": "Dividendo",
                "date": formatted_date,
                "value": value
            }

            formatted_data = format_text_in_json(formatted_data)
            content[formatted_date] = formatted_data

        all_content[ticker] = content

    return all_content

tickers = sys.argv[1:]
all_dividends = extract_dividends(tickers)

# Serializa para JSON com indentação e encoding correto
json_data = json.dumps(all_dividends, ensure_ascii=False)

print(json_data)
# Salva como string no Redis
r.set("dividends", json_data)

print("Dividendos salvos no Redis com sucesso.")
