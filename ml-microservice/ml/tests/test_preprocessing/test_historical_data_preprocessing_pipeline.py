import unittest
import os
import pandas as pd
from unittest.mock import patch
import logging
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline

class TestHistoricalDataPreprocessingPipelineIntegration(unittest.TestCase):

    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.TimeSeriesEngineeringLayer.process_historical_data')
    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.FeatureEngineeringLayer.process_historical_weekly_data')
    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.CleaningLayer.process_historical_weekly_data')
    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.FileIngestionLayer.process')
    def test_historical_data_preprocessing_pipeline_weekly(self, mock_ts_engineer, mock_feature_engineer, mock_clean_data, mock_ingest_data):
        """
        Test the full historical data pipeline for weekly data.
        """
        # Mock data to return at all stages
        mock_ingest_data.return_value = pd.DataFrame({
            'product_id': ['A', 'B'],
            'product_name': ['Product A', 'Product B'],
            'monday': [10, 20],
            'tuesday': [15, 25],
            'wednesday': [20, 30],
            'thursday': [5, 10],
            'friday': [25, 35],
            'saturday': [30, 40],
            'sunday': [35, 45],
            'quantity': [140, 205],
            'value': [1000, 1500],
            'in_stock': [1, 1],
            'category': ['cat1', 'cat2'],
            'year': [2023, 2023],
            'week': [1, 1]
        })

        mock_clean_data.return_value = mock_ingest_data.return_value
        mock_feature_engineer.return_value = mock_ingest_data.return_value
        mock_ts_engineer.return_value = mock_ingest_data.return_value

        # Initialise the pipeline for historical weekly data preprocessing
        pipeline = HistoricalDataPreprocessingPipeline(data_path='/dummy/path', data_type='weekly')

        # Run the pipeline
        final_data = pipeline.run()

        # Check if data was processed correctly
        self.assertIsNotNone(final_data, "Pipeline should return a DataFrame")
        self.assertEqual(len(final_data), 2, "Final data should have two rows")
        self.assertIn('product_id', final_data.columns, "Final data should contain 'product_id' column")

    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.FileIngestionLayer.process')
    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.CleaningLayer.process_historical_daily_data')
    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.FeatureEngineeringLayer.process_historical_daily_data')
    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.TimeSeriesEngineeringLayer.process_historical_data')
    def test_historical_pipeline_daily(self, mock_ts_engineer, mock_feature_engineer, mock_clean_data, mock_ingest_data):
        """
        Test the full historical data pipeline for daily data.
        """
        # Mock data to return at all stages
        mock_ingest_data.return_value = pd.DataFrame({
            'product_id': ['A', 'B'],
            'product_name': ['pro1', 'pro2'],
            'category': ['cat1', 'cat2'],
            'date': ['2023-01-01', '2023-01-02'],
        })

        mock_clean_data.return_value = mock_ingest_data.return_value
        mock_feature_engineer.return_value = mock_ingest_data.return_value
        mock_ts_engineer.return_value = mock_ingest_data.return_value

        # Initialise the pipeline for historical daily data preprocessing
        pipeline = HistoricalDataPreprocessingPipeline(data_path='/dummy/path', data_type='daily')

        # Run the pipeline
        final_data = pipeline.run()

        # Check if data was processed correctly
        self.assertIsNotNone(final_data, "Pipeline should return a DataFrame")
        self.assertEqual(len(final_data), 2, "Final data should have two rows")
        self.assertIn('product_id', final_data.columns, "Final data should contain 'product_id' column")

if __name__ == '__main__':
    unittest.main()
