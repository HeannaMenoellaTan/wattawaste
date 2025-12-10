import pandas as pd
import mysql.connector
import joblib
import json

# === LOAD THE TRAINED MODEL ===
model = joblib.load("compost_model.pkl")

# === CONNECT TO DATABASE ===
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="wattawaste_system"
)
cursor = db.cursor(dictionary=True)

# === FETCH THE LATEST SENSOR VALUES ===
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
ORDER BY t.Temp_Id DESC
LIMIT 1
""")
data = pd.DataFrame(cursor.fetchall())

# === MAKE SURE IT'S FLOAT ===
data = data.astype(float)

# === PREDICT READINESS ===
prediction = model.predict(data)[0]

# === PRINT AS JSON (so PHP can read it easily) ===
print(json.dumps({"readiness": round(float(prediction), 2)}))

cursor.close()
db.close()
