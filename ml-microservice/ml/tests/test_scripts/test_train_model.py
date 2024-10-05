import unittest
from unittest.mock import patch, MagicMock
import pandas as pd
import os
from ml.scripts.train_model import main as train_main
from ml.preprocessing.data_splitting_layer import DataSplittingLayer

class TestTrainModel(unittest.TestCase):

    @patch('pandas.read_csv')
    @patch('ml.scripts.train_model.Trainer.run')
    @patch('ml.scripts.train_model.DataSplittingLayer.split_timeline_in_two_halves')
    def test_main_success(self, mock_split, mock_trainer_run, mock_read_csv):
        """
        Test that the train_model script runs successfully.
        """
        # Mock data
        mock_df = pd.DataFrame({
            'product_id_encoded': [1, 2, 3, 4, 5],
            'category_encoded': [1, 1, 2, 2, 3],
            'quantity_lag_1': [10, 20, 30, 40, 50],
            'quantity_lag_7': [5, 15, 25, 35, 45],
            'quantity_rolling_avg_7': [6, 16, 26, 36, 46],
            'quantity_rolling_avg_30': [7, 17, 27, 37, 47],
            'month_cos': [0.5, 0.6, 0.7, 0.8, 0.9],
            'month_sin': [0.8, 0.9, 1.0, 0.5, 0.6],
            'weekday_cos': [0.1, 0.2, 0.3, 0.4, 0.5],
            'weekday_sin': [0.4, 0.5, 0.6, 0.7, 0.8],
            'in_stock': [1, 0, 1, 0, 1],
            'per_item_value': [100, 200, 300, 400, 500],
            'date': pd.to_datetime(['2023-01-01', '2023-01-02', '2023-01-03', '2023-01-04', '2023-01-05']),
            'quantity': [2, 3, 6, 12, 7]
        })

        # Mock reading CSV data
        mock_read_csv.return_value = mock_df

        # Mock data splitting
        X_train = mock_df.drop(columns=['quantity'])
        y_train = mock_df['quantity']
        X_test = X_train.copy()
        y_test = y_train.copy()
        mock_split.return_value = (X_train, y_train, X_test, y_test)

        # Mock Trainer's run method
        mock_trainer_run.return_value = ('mock_model', 'mock_model_path.pkl')

        # Run the train model function
        model_path, X_test_output, y_test_output = train_main('/dummy/path', 'xgboost', 'mock_output_path')

        # Check that the processes happen
        mock_read_csv.assert_called_once_with('/dummy/path')
        mock_split.assert_called_once()
        mock_trainer_run.assert_called_once_with(X_train, y_train)

        # Check if the model path and test data are returned correctly
        self.assertEqual(model_path, 'mock_model_path.pkl')
        pd.testing.assert_frame_equal(X_test_output, X_test)
        pd.testing.assert_series_equal(y_test_output, y_test)

    @patch('pandas.read_csv')
    def test_main_file_not_found(self, mock_read_csv):
        """
        Test that an error is logged when the data file is not found.
        """
        # Simulate a file not found error
        mock_read_csv.side_effect = FileNotFoundError("File not found")

        # Set up the arguments
        data_path = 'nonexistent_data_path.csv'
        model_type = 'xgboost'
        output_path = 'mock_output_path'

        # Call the main function and check for error logging
        with self.assertLogs(level='ERROR') as log:
            train_main(data_path, model_type, output_path)

        self.assertIn("Error loading data", log.output[0])

    @patch('pandas.read_csv')
    @patch('ml.scripts.train_model.DataSplittingLayer.split_timeline_in_two_halves')
    def test_main_data_splitting_error(self, mock_split, mock_read_csv):
        """
        Test that an error is logged when data splitting fails.
        """
        # Mock data loading
        mock_df = pd.DataFrame({
            'product_id_encoded': [1, 2, 3, 4, 5],
            'category_encoded': [1, 1, 2, 2, 3],
            'quantity_lag_1': [10, 20, 30, 40, 50],
            'quantity': [2, 3, 6, 12, 7]
        })
        mock_read_csv.return_value = mock_df

        # Simulate an error during data splitting
        mock_split.side_effect = Exception("Data splitting error")

        # Run the train model script and check for error logging
        with self.assertLogs(level='ERROR') as log:
            train_main('/dummy/path', 'xgboost', 'mock_output_path')

        self.assertIn("Error during data splitting", log.output[0])

    @patch('pandas.read_csv')
    @patch('ml.scripts.train_model.DataSplittingLayer.split_timeline_in_two_halves')
    @patch('ml.scripts.train_model.Trainer.run')
    def test_main_training_error(self, mock_trainer_run, mock_split, mock_read_csv):
        """
        Test that an error is logged when model training fails.
        """
        # Mock data loading and splitting
        mock_df = pd.DataFrame({
            'product_id_encoded': [1, 2, 3, 4, 5],
            'category_encoded': [1, 1, 2, 2, 3],
            'quantity_lag_1': [10, 20, 30, 40, 50],
            'quantity': [2, 3, 6, 12, 7]
        })
        mock_read_csv.return_value = mock_df

        X_train = mock_df.drop(columns=['quantity'])
        y_train = mock_df['quantity']
        X_test = X_train.copy()
        y_test = y_train.copy()
        mock_split.return_value = (X_train, y_train, X_test, y_test)

        # Simulate an error during training
        mock_trainer_run.side_effect = Exception("Training error")

        # Run the train model script and check for error logging
        with self.assertLogs(level='ERROR') as log:
            train_main('/dummy/path', 'xgboost', 'mock_output_path')

        self.assertIn("Error during model training", log.output[0])

if __name__ == '__main__':
    unittest.main()
