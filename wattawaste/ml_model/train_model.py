import pandas as pd
import mysql.connector
from sklearn.ensemble import RandomForestRegressor
import joblib

# === CONNECT TO DATABASE ===
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="wattawaste_system"
)
cursor = db.cursor(dictionary=True)

# === FETCH SENSOR DATA ===
cursor.execute("""
SELECT 
    t.Temp_Ave AS temperature,
    t.Temp_Range AS humidity,
    g.Gas_Lvl AS gas,
    p.pH_Value AS ph,
    w.Weight_Lvl AS weight
FROM temperatures t
JOIN gas g ON g.Gas_Id = t.Temp_Id
JOIN ph p ON p.pH_Id = t.Temp_Id
JOIN weights w ON w.Weight_Id = t.Temp_Id
""")
data = pd.DataFrame(cursor.fetchall())

# 🧩 Convert all values to float (fixes the Decimal error)
data = data.astype(float)

# === FAKE READINESS LABEL (temporary until you collect real data) ===
data["readiness"] = (
    (data["temperature"].clip(30, 60) / 60 * 0.25) +
    (data["humidity"].clip(40, 80) / 80 * 0.25) +
    ((14 - abs(data["ph"] - 7)) / 14 * 0.25) +
    ((100 - data["gas"].clip(0, 100)) / 100 * 0.25)
) * 100

# === TRAIN RANDOM FOREST ===
X = data[["temperature", "humidity", "gas", "ph", "weight"]]
y = data["readiness"]

model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)

joblib.dump(model, "compost_model.pkl")
print("✅ Model trained and saved as compost_model.pkl!")

cursor.close()
db.close()
