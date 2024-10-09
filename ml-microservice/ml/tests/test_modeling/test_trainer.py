import unittest
from unittest.mock import patch, MagicMock
import numpy as np
import pandas as pd
from ml.modeling.trainer import Trainer
from sklearn.model_selection import TimeSeriesSplit

class TestTrainer(unittest.TestCase):

    @patch('ml.modeling.trainer.xgb.XGBRegressor')
    @patch('ml.modeling.trainer.joblib.dump')
    def test_save_model(self, mock_joblib_dump, mock_xgb):
        """
        Test the saving process of the model.
        """
        # Mock a model and path
        mock_model = MagicMock()
        mock_xgb.return_value = mock_model

        # Create a trainer  and invoke the class method
        trainer = Trainer(model_type='xgboost', output_path='mock_output_path')
        model_path = trainer.save_model(mock_model)

        # Check that joblib.dump is called
        mock_joblib_dump.assert_called_once_with(mock_model, model_path)
        self.assertTrue(model_path.startswith('mock_output_path/xgboost_model_'))

    @patch('ml.modeling.trainer.RandomizedSearchCV')
    def test_random_search_train_model(self, mock_random_search):
        """
        Test the random search process.
        """
        # Mock RandomizedSearchCV and its return value
        mock_search_instance = mock_random_search.return_value
        mock_search_instance.best_estimator_ = 'best_model'

        # Define test data
        X_train = np.random.rand(10, 3)
        y_train = np.random.rand(10)

        # Create a trainer and invoke class method
        trainer = Trainer(model_type='xgboost')
        tscv = TimeSeriesSplit(n_splits=3)
        best_model = trainer.random_search_train_model(X_train, y_train, tscv)

        # Ensure the search was performed
        mock_random_search.assert_called_once()
        self.assertEqual(best_model, 'best_model')

    def test_setup_time_series_split(self):
        """
        Test the set up of the time series split.
        """
        # Create a trainer
        trainer = Trainer(model_type='xgboost')

        # Test for a normal case with more than 5 samples
        X_train = pd.DataFrame(np.random.rand(10, 3))
        tscv = trainer.setup_time_series_split(X_train, n_splits=5)
        self.assertEqual(tscv.n_splits, 5)

        # Test for a small dataset where n_splits should adjust dynamically
        X_train_small = pd.DataFrame(np.random.rand(3, 3))
        tscv_small = trainer.setup_time_series_split(X_train_small, n_splits=5)
        self.assertEqual(tscv_small.n_splits, 2)

    @patch('ml.modeling.trainer.Trainer.random_search_train_model')
    @patch('ml.modeling.trainer.Trainer.save_model')
    def test_run(self, mock_save_model, mock_random_search):
        """
        Test the full process.
        """
        # Mock the methods
        mock_random_search.return_value = 'best_model'
        mock_save_model.return_value = 'model_path.pkl'

        # Define test data
        X_train = pd.DataFrame(np.random.rand(10, 3))
        y_train = pd.Series(np.random.rand(10))

        # Create a trainer
        trainer = Trainer(model_type='xgboost')

        # Run the trainer
        best_model, model_path = trainer.run(X_train, y_train)

        # Check if the methods were called
        mock_random_search.assert_called_once_with(X_train, y_train, unittest.mock.ANY)
        mock_save_model.assert_called_once_with('best_model')
        self.assertEqual(best_model, 'best_model')
        self.assertEqual(model_path, 'model_path.pkl')

if __name__ == '__main__':
    unittest.main()
