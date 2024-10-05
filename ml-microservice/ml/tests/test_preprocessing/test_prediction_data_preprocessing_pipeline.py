import unittest
import pandas as pd
from unittest.mock import patch
from ml.preprocessing.prediction_data_preprocessing_pipeline import PredictionDataPreprocessingPipeline

class TestPredictionDataPreprocessingPipelineIntegration(unittest.TestCase):

    @patch('ml.preprocessing.prediction_data_preprocessing_pipeline.TimeSeriesEngineeringLayer.process_prediction_data')
    @patch('ml.preprocessing.prediction_data_preprocessing_pipeline.FeatureEngineeringLayer.process_prediction_data')
    @patch('ml.preprocessing.prediction_data_preprocessing_pipeline.CleaningLayer.process_prediction_data')
    @patch('ml.preprocessing.prediction_data_preprocessing_pipeline.PredictionDataIngestionLayer.process')
    def test_prediction_data_preprocessing_pipeline(self, mock_ingest_data, mock_clean_data, mock_feature_engineer, mock_ts_engineer):
        """
        Test the full prediction data preprocessing pipeline.
        """
        # Mock data returns
        mock_ingest_data.return_value = pd.DataFrame({
            'product_name': ['Product A', 'Product B'],
            'category': ['cat1', 'cat2'],
            'per_item_value': [100, 200],
            'in_stock': [1, 1],
            'date': ['2023-01-01', '2023-01-02'],
        })
        mock_clean_data.return_value = mock_ingest_data.return_value
        mock_feature_engineer.return_value = mock_ingest_data.return_value
        mock_ts_engineer.return_value = pd.DataFrame({
            'product_id_encoded': [1, 2],
            'category_encoded': [10, 20],
            'per_item_value': [100, 200],
            'in_stock': [1, 1],
            'quantity_lag_1': [100, 200],
            'quantity_lag_7': [150, 250],
            'quantity_lag_30': [100, 200],
            'quantity_lag_90': [100, 200],
            'quantity_lag_365': [100, 200],
            'quantity_rolling_avg_7': [120, 220],
            'quantity_rolling_avg_14': [120, 220],
            'quantity_rolling_avg_30': [110, 210],
            'quantity_rolling_avg_90': [120, 220],
            'quantity_rolling_avg_365': [120, 220],
            'month_cos': [0.87, 0.5],
            'month_sin': [0.5, 0.87],
            'weekday_cos': [1, 0.87],
            'weekday_sin': [0, 0.5],
            'source_product_id': ['A', 'B'],
            'date': ['2023-01-01', '2023-01-02']
        })

        # Define test data
        test_data = {
            'prediction_dates': ['2023-01-03', '2023-01-04'],
            'products': [
                {
                    'details': {
                        'source_product_id': 'A',
                        'product_name': 'Product A',
                        'category': 'cat1',
                        'per_item_value': 100,
                        'in_stock': 1,
                    },
                    'historical_sales': [
                        {'date': '2023-01-01', 'quantity': 100},
                        {'date': '2023-01-02', 'quantity': 200}
                    ]
                },
                {
                    'details': {
                        'source_product_id': 'B',
                        'product_name': 'Product B',
                        'category': 'cat2',
                        'per_item_value': 200,
                        'in_stock': 1,
                    },
                    'historical_sales': [
                        {'date': '2023-01-01', 'quantity': 150},
                        {'date': '2023-01-02', 'quantity': 250}
                    ]
                }
            ]
        }

        # Initialise the pipeline for prediction data preprocessing
        pipeline = PredictionDataPreprocessingPipeline(data=test_data)
        final_data = pipeline.run()

        # Check the data was processed correctly
        self.assertIsNotNone(final_data, "Pipeline should return a DataFrame")
        self.assertEqual(len(final_data), 2, "Final data should have two rows")

        expected_columns = ['product_id_encoded', 'category_encoded', 'per_item_value', 'in_stock', 'quantity_lag_1', 'quantity_lag_7', 'quantity_rolling_avg_7', 'quantity_rolling_avg_30', 'month_sin', 'month_cos', 'weekday_cos', 'weekday_sin']
        missing_columns = [col for col in expected_columns if col not in final_data.columns]
        self.assertEqual(len(missing_columns), 0, f"Missing columns: {missing_columns}")

if __name__ == '__main__':
    unittest.main()
