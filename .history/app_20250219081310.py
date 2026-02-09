from flask import Flask, request, jsonify
import random

app = Flask(__name__)

@app.route('/predict', methods=['POST'])
def predict_crime():
    data = request.get_json()
    location = data['location']
    crime_type = data['crime_type']
    
    risk_levels = ["Low", "Medium", "High"]
    risk_prediction = random.choice(risk_levels)  # Simulated ML Prediction
    
    return jsonify({"risk_level": risk_prediction})

if __name__ == '__main__':
    app.run(debug=True)
