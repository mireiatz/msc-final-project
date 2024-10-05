import unittest
from unittest.mock import patch
from ml.scripts.build_model import main as build_main
import argparse

class TestBuildModel(unittest.TestCase):

    @patch('ml.scripts.build_model.evaluate_main')
    @patch('ml.scripts.build_model.train_main')
    def test_build_model_success(self, mock_train_main, mock_evaluate_main):
        """
        Test the 'build_model' script to ensure training and evaluation are triggered correctly.
        """
        # Mock the training function to return a model path and test data
        mock_train_main.return_value = ('mock_model_path.pkl', 'mock_X_test', 'mock_y_test')

        # Run the script
        args = argparse.Namespace(data_path='/dummy/path', model_type='xgboost', output_path='mock_output_path')
        build_main(args)

        # Ensure train_main was called with the correct arguments
        mock_train_main.assert_called_once_with('/dummy/path', 'xgboost', 'mock_output_path')

        # Ensure evaluate_main was called with the correct model path and test data
        mock_evaluate_main.assert_called_once_with('mock_model_path.pkl', 'mock_X_test', 'mock_y_test')

    @patch('ml.scripts.build_model.train_main', side_effect=Exception("Training failed"))
    def test_build_model_training_failure(self, mock_train_main):
        """
        Test the 'build_model' script's error handling if training fails.
        """
        args = argparse.Namespace(data_path='/dummy/path', model_type='xgboost', output_path='mock_output_path')

        with self.assertLogs(level='ERROR') as log:
            with self.assertRaises(Exception):
                build_main(args)

        self.assertIn("Error in model build pipeline", log.output[0])

if __name__ == '__main__':
    unittest.main()
