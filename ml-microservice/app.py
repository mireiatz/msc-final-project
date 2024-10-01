from flask import Flask, request, jsonify
import os
import shutil
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
from logging_config import setup_logging

setup_logging()

app = Flask(__name__)

@app.route('/preprocess-historical-data', methods=['POST'])
def preprocess_historical_data():
    """
    Flask route to handle the preprocessing of historical data.
    """
    if 'file' not in request.files:
        return jsonify({'error': 'No file uploaded'}), 400

    file = request.files['file']

    if file.filename == '':
        return jsonify({'error': 'No selected file'}), 400

    # Save the file temporarily
    file_path = os.path.join('/tmp', file.filename)
    file.save(file_path)

    try:
        # Run the preprocessing pipeline
        HistoricalDataPreprocessingPipeline(
            data_path=file_path
        ).run()

        return jsonify({"status": "Success, data processed"}), 200

    except Exception as e:
        return jsonify({"error": f"Error processing data: {str(e)}"}), 500

if __name__ == "__main__":
    # Run the Flask application
    app.run(host="0.0.0.0", port=5002)
