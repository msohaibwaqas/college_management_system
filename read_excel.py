import pandas as pd
import json

try:
    df = pd.read_excel('resources/views/timetable.xlsx', header=None)
    print(df.head(10).to_string())
except Exception as e:
    print(f"Error reading excel: {e}")
