from flask import Flask, request, jsonify
import os
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
from logging_config import setup_logging, logging
import json
from app_config import app_config

app = Flask(__name__)

@app.route('/export-sales-data', methods=['POST'])
def export_sales_data():
    """
    Flask route to handle the preprocessing of historical data.
    """
    # No file found in the request
    if 'file' not in request.files:
        return jsonify({'error': 'No file uploaded'}), 400

    # Get the details
    file = request.files['file']
    if file.filename == '':
        return jsonify({'error': 'No selected file'}), 400

    # Save the file temporarily
    file_path = os.path.join('/tmp', file.filename)
    file.save(file_path)

    # Retrieve and parse the JSON metadata from the form
    metadata = request.form.get('metadata')

    if not metadata:
        return jsonify({'error': 'No metadata provided'}), 400

    try:
        metadata = json.loads(metadata)  # Convert JSON string to dictionary
    except json.JSONDecodeError:
        return jsonify({'error': 'Invalid metadata format, must be valid JSON'}), 400

    try:
        # Handle historical data
        if metadata.get('type') == 'historical':
            data_type = metadata.get('format')

            if data_type == 'weekly':
                # Run the historical weekly preprocessing pipeline
                HistoricalDataPreprocessingPipeline(
                    data_path=file_path,
                    data_type='weekly'
                ).run()
            elif data_type == 'daily':
                # Run the historical daily preprocessing pipeline
                HistoricalDataPreprocessingPipeline(
                    data_path=file_path,
                    data_type='daily'
                ).run()
        else:
            return jsonify({"status": "Warning, no data processed, use data type historical"}), 200
            pass

        return jsonify({"status": "Success, data processed"}), 200

    except Exception as e:
        return jsonify({"error": f"Error processing data: {str(e)}"}), 500

@app.route('/predict-demand', methods=['POST'])
def predict_demand():
    """
    Flask route to make predictions.
    """
    try:

        return jsonify({"status": "Success, data processed"}), 200

    except Exception as e:
        logging.error(f"Error: {e}")
        return jsonify({'error': str(e)}), 500


if __name__ == "__main__":
    setup_logging()

    # Run the Flask application
    app.run(host="0.0.0.0", port=5002, debug=True)
