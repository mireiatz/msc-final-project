from flask import Flask, request, jsonify
import os
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
from logging_config import setup_logging, logging
import json
from app_config import app_config
import os
import sys
import subprocess

app = Flask(__name__)

def run_in_background(script_name, *args):
    """
    Run a script in the background with the given arguments.
    """
    # Build the full script path
    script_path = os.path.join(app_config.SCRIPTS, script_name)

    # Get the project root directory
    project_root = os.path.abspath(os.path.dirname(__file__))

    # Copy the current environment and set the PYTHONPATH to the project root
    env = os.environ.copy()
    env['PYTHONPATH'] = project_root

    # Build the command to run the script with provided arguments
    command = ['python', script_path] + list(args)

    # Execute the script asynchronously
    subprocess.Popen(command, env=env, cwd=project_root)

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

            if data_type in ['weekly', 'daily']:
                # Run the preprocessing pipeline in the background
                run_in_background('preprocess_historical_data.py', '--data_path', file_path, '--data_type', data_type)

                return jsonify({"status": f"Preprocessing started for {data_type} data"}), 200
            else:
                return jsonify({"status": "Invalid data type"}), 400
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
