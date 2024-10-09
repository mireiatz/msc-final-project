import unittest
from unittest.mock import patch
from ml.scripts.preprocess_historical_data import main
import argparse

class TestHistoricalDataPreprocessingScript(unittest.TestCase):

    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.HistoricalDataPreprocessingPipeline.run')
    def test_main_success(self, mock_run):
        """
        Test that the historical preprocessing script runs with valid arguments.
        """
        # Mock arguments
        args = argparse.Namespace(
            data_path='/dummy/data/path',
            output_path='/dummy/output/path',
            data_type='daily'
        )

        # Call the main function
        main(args)

        # Assert that the pipeline run method was called once
        mock_run.assert_called_once()

    @patch('ml.preprocessing.historical_data_preprocessing_pipeline.HistoricalDataPreprocessingPipeline.run')
    def test_main_missing_output_path(self, mock_run):
        """
        Test that the historical preprocessing script runs successfully when output_path is missing.
        """
        # Mock arguments without output_path
        args = argparse.Namespace(
            data_path='/dummy/data/path',
            output_path=None,
            data_type='weekly'
        )

        # Call the main function
        main(args)

        # Assert that the pipeline run method was called once
        mock_run.assert_called_once()

if __name__ == '__main__':
    unittest.main()
