import yfinance as yf
import pandas as pd
from json import loads, dumps

cota = yf.Ticker("PORD11.SA")

hist = cota.history(period="2y")

hist = hist.loc[hist['Dividends'] != 0.0]

hist = hist.reset_index()

hist['Date'] = pd.to_datetime(hist['Date']).dt.strftime('%Y-%m-%d')

dados_b3 = hist[['Date','Close','Dividends']]

dados_b3.to_json('dadosb3.json',orient="records")

result = dados_b3.to_json(orient="records")
parsed = loads(result)

print(parsed)
