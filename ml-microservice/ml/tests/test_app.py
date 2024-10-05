import unittest
from flask import json
from io import BytesIO
from unittest.mock import patch
from app import app
import pandas as pd

class TestApp(unittest.TestCase):

    def setUp(self):
        """
        Set up test app client and indicate testing mode.
        """
        self.app = app.test_client()
        self.app.testing = True

    def test_export_sales_data_no_file(self):
        """
        Test the 'export-sales-data' endpoint with no file uploaded.
        """
        # Make the request
        response = self.app.post('/export-sales-data')

        # Check error
        self.assertEqual(response.status_code, 400)
        self.assertIn('No file uploaded', response.json['error'])

    def test_export_sales_data_no_metadata(self):
        """
        Test the 'export-sales-dat'a endpoint with no metadata provided.
        """
        # Make the request
        data = {
            'file': (BytesIO(b'mock data'), 'mock_file.csv')
        }
        response = self.app.post('/export-sales-data', data=data)

        # Check error
        self.assertEqual(response.status_code, 400)
        self.assertIn('No metadata provided', response.json['error'])

    def test_export_sales_data_invalid_metadata(self):
        """
        Test the 'export-sales-data' endpoint with invalid metadata.
        """
        # Make the request
        data = {
            'file': (BytesIO(b'mock data'), 'mock_file.csv'),
            'metadata': 'invalid json'
        }
        response = self.app.post('/export-sales-data', data=data)

        # Check error
        self.assertEqual(response.status_code, 400)
        self.assertIn('Invalid metadata format', response.json['error'])

    @patch('app.run_in_background')
    def test_export_sales_data_success(self, mock_run_in_background):
        """
        Test successful historical weekly data export.
        """
        # Make the request
        data = {
            'file': (BytesIO(b'mock data'), 'mock_file.csv'),
            'metadata': json.dumps({'type': 'historical', 'format': 'weekly'})
        }
        response = self.app.post('/export-sales-data', data=data)

        # Check success
        self.assertEqual(response.status_code, 200)
        self.assertIn('Preprocessing started for weekly data', response.json['status'])

    @patch('app.PredictionDataPreprocessingPipeline.run')
    @patch('app.Predictor.run_live_predictions')
    def test_predict_demand_success(self, mock_predictor_run, mock_pipeline_run):
        """
        Test the 'predict-demand' endpoint for successful predictions.
        """
        # Mock the preprocessed data
        mock_pipeline_run.return_value = pd.DataFrame({
            'source_product_id': ['A', 'B'],
            'date': ['2023-01-01', '2023-01-02'],
            'product_id_encoded': [1, 2],
            'category_encoded': [10, 20],
            'in_stock': [1, 1],
            'per_item_value': [100, 200],
        })

        # Mock the prediction result
        mock_predictor_run.return_value = pd.DataFrame({
            'product_id': ['A', 'B'],
            'date': ['2023-01-01', '2023-01-02'],
            'value': [150, 250]
        })

        # Make request
        data = {
            "prediction_dates": ["2023-01-03", "2023-01-04"],
            "products": [
                {
                    "details": {
                        "source_product_id": "A",
                        "product_name": "Product A",
                        "category": "cat1",
                        "per_item_value": 100,
                        "in_stock": 1,
                    },
                    "historical_sales": [
                        {"date": "2023-01-01", "quantity": 100},
                        {"date": "2023-01-02", "quantity": 200}
                    ]
                },
                {
                    "details": {
                        "source_product_id": "B",
                        "product_name": "Product B",
                        "category": "cat2",
                        "per_item_value": 200,
                        "in_stock": 1,
                    },
                    "historical_sales": [
                        {"date": "2023-01-01", "quantity": 150},
                        {"date": "2023-01-02", "quantity": 250}
                    ]
                }
            ]
        }
        response = self.app.post('/predict-demand', json=data)

        # Check success and correct data in response
        self.assertEqual(response.status_code, 200)
        self.assertIn('Success, demand predicted', response.json['status'])
        predictions = json.loads(response.data)
        predictions_list = json.loads(predictions['predictions'])
        self.assertEqual(len(predictions), 2)
        self.assertEqual(predictions_list[0]['value'], 150)
        self.assertEqual(predictions_list[1]['value'], 250)

    def test_predict_demand_invalid_json(self):
        """
        Test the 'predict-demand' endpoint with invalid JSON.
        """
        # Make request
        response = self.app.post('/predict-demand', data="invalid json")

        # Check error
        self.assertEqual(response.status_code, 500)
        self.assertIn('error', response.json)

    def test_export_sales_data_invalid_data_type(self):
        """
        Test the export-sales-data endpoint with invalid data type.
        """
        # Make request
        data = {
            'file': (BytesIO(b'mock data'), 'mock_file.csv'),
            'metadata': json.dumps({'type': 'historical', 'format': 'invalid_format'})
        }
        response = self.app.post('/export-sales-data', data=data)

        # Check error
        self.assertEqual(response.status_code, 400)
        self.assertIn('Invalid data format', response.json['status'])

    def test_predict_demand_missing_fields(self):
        """
        Test the predict-demand endpoint with missing product details.
        """
        # Make request
        data = {
            "prediction_dates": ["2023-01-03", "2023-01-04"],
            "products": [
                {
                    # Missing 'details' and 'historical_sales'
                }
            ]
        }
        response = self.app.post('/predict-demand', json=data)

        # Check error
        self.assertEqual(response.status_code, 500)
        self.assertIn('error', response.json)

if __name__ == '__main__':
    unittest.main()
