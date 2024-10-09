import pandas as pd
from ml.preprocessing.data_splitting_layer import DataSplittingLayer
import unittest

class TestDataSplittingLayer(unittest.TestCase):

    def setUp(self):
        """
        Set up test DataSplittingLayer and data sample.
        """
        self.mock_data = pd.DataFrame({
            'date': pd.date_range(start='2023-01-01', periods=10, freq='D'),
            'feature1': range(10),
            'feature2': range(10, 20),
            'target': range(20, 30)
        })

        self.features = ['feature1', 'feature2']
        self.target = 'target'

        # Initialise the layer
        self.layer = DataSplittingLayer(self.mock_data, features=self.features, target=self.target)

    def test_split_timeline_in_two_halves(self):
        """
        Test the splitting of a timeline down the middle.
        """
        # Invoke the class method
        X_train, y_train, X_test, y_test = self.layer.split_timeline_in_two_halves()

        # Assert that the train set is half the size of the total dataset
        self.assertEqual(len(X_train), len(self.mock_data) // 2)
        self.assertEqual(len(X_test), len(self.mock_data) - len(X_train))

        # Check that the feature columns are correct in both train and test sets
        self.assertListEqual(X_train.columns.tolist(), self.features)
        self.assertListEqual(X_test.columns.tolist(), self.features)

        # Check that the target column is correctly split
        self.assertEqual(y_train.name, self.target)
        self.assertEqual(y_test.name, self.target)

    def test_split_time_based(self):
        """
        Test the splitting based on a date.
        """
        # Invoke the class method
        split_date = '2023-01-05'
        X_train, y_train, X_test, y_test = self.layer.time_based_split(split_date)

        # The split occurs on the given date
        self.assertTrue((self.mock_data['date'] <= split_date).sum(), len(X_train))
        self.assertTrue((self.mock_data['date'] > split_date).sum(), len(X_test))

if __name__ == '__main__':
    unittest.main()
