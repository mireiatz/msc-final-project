import unittest
from unittest.mock import patch
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from ml.preprocessing.time_series_engineering_layer import TimeSeriesEngineeringLayer
import numpy.testing as npt

class TestTimeSeriesEngineeringLayer(unittest.TestCase):

    def setUp(self):
        """
        Set up test FeatureEngineeringLayer and data sample.
        """
        # Sample 30 day data for lag and rolling feature creation
        product_1_month_9 = pd.date_range(start='2024-09-01', periods=15)
        product_1_month_1 = pd.date_range(start='2024-01-01', periods=15)
        product_2_month_8 = pd.date_range(start='2024-08-01', periods=15)
        product_2_month_3 = pd.date_range(start='2024-03-01', periods=15)
        values_1_15 = list(range(1, 16))
        values_16_31 = list(range(16, 31))
        self.data = pd.DataFrame({
            'product_id': ['A'] * 15 + ['B'] * 15 + ['A'] * 15 + ['B'] * 15,
            'date': list(product_1_month_9) + list(product_2_month_8) + list(product_1_month_1) + list(product_2_month_3),
            'quantity': values_16_31 + values_16_31 + values_1_15 + values_1_15,
            'value': values_16_31 + values_16_31 + values_1_15 + values_1_15,
        })

        # Initialise the layer
        self.layer = TimeSeriesEngineeringLayer(self.data)

    def test_create_time_series_features(self):
        """
        Test the creation of lag and rolling average columns for different periods.
        """
        # Invoke method from the class
        df = self.layer.create_time_series_features(self.data, 'quantity', [1, 5, 10, 15, 30])

        # Lag columns exist
        self.assertIn('quantity_lag_1', df.columns)
        self.assertIn('quantity_lag_5', df.columns)
        self.assertIn('quantity_lag_10', df.columns)
        self.assertIn('quantity_lag_15', df.columns)
        self.assertIn('quantity_lag_30', df.columns)

        # Values are shifted the specified amount of days for Product A

        # Expected values with np.nan
        expected_lag_1 = [np.nan] + list(map(float, range(1, 30)))
        actual_lag_1 = df[df['product_id'] == 'A']['quantity_lag_1'].tolist()
        npt.assert_array_almost_equal(actual_lag_1, expected_lag_1, decimal=7)

        expected_lag_5 = [np.nan] * 5 + list(map(float, range(1, 26)))
        actual_lag_5 = df[df['product_id'] == 'A']['quantity_lag_5'].tolist()
        npt.assert_array_almost_equal(actual_lag_5, expected_lag_5, decimal=7)

        expected_lag_10 = [np.nan] * 10 + list(map(float, range(1, 21)))
        actual_lag_10 = df[df['product_id'] == 'A']['quantity_lag_10'].tolist()
        npt.assert_array_almost_equal(actual_lag_10, expected_lag_10, decimal=7)

        expected_lag_15 = [np.nan] * 15 + list(map(float, range(1, 16)))
        actual_lag_15 = df[df['product_id'] == 'A']['quantity_lag_15'].tolist()
        npt.assert_array_almost_equal(actual_lag_15, expected_lag_15, decimal=7)

        expected_lag_30 = [np.nan] * 30
        actual_lag_30 = df[df['product_id'] == 'A']['quantity_lag_30'].tolist()
        npt.assert_array_almost_equal(actual_lag_30, expected_lag_30, decimal=7)

        # Values are shifted the specified amount of days for Product B
        expected_lag_1 = [np.nan] + list(map(float, range(1, 30)))
        actual_lag_1 = df[df['product_id'] == 'B']['quantity_lag_1'].tolist()
        npt.assert_array_almost_equal(actual_lag_1, expected_lag_1, decimal=7)

        expected_lag_5 = [np.nan] * 5 + list(map(float, range(1, 26)))
        actual_lag_5 = df[df['product_id'] == 'B']['quantity_lag_5'].tolist()
        npt.assert_array_almost_equal(actual_lag_5, expected_lag_5, decimal=7)

        expected_lag_10 = [np.nan] * 10 + list(map(float, range(1, 21)))
        actual_lag_10 = df[df['product_id'] == 'B']['quantity_lag_10'].tolist()
        npt.assert_array_almost_equal(actual_lag_10, expected_lag_10, decimal=7)

        expected_lag_15 = [np.nan] * 15 + list(map(float, range(1, 16)))
        actual_lag_15 = df[df['product_id'] == 'B']['quantity_lag_15'].tolist()
        npt.assert_array_almost_equal(actual_lag_15, expected_lag_15, decimal=7)

        expected_lag_30 = [np.nan] * 30
        actual_lag_30 = df[df['product_id'] == 'B']['quantity_lag_30'].tolist()
        npt.assert_array_almost_equal(actual_lag_30, expected_lag_30, decimal=7)

        # Invoke method from the class
        df = self.layer.create_time_series_features(self.data, 'quantity', [7, 30])

        # Rolling average columns exist
        self.assertIn('quantity_rolling_avg_7', df.columns)
        self.assertIn('quantity_rolling_avg_30', df.columns)

        # Manually calculated rolling averages for a 7-day and 30-day window
        expected_rolling_avg_7 = [
            1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0,
            17.0, 18.0, 19.0, 20.0, 21.0, 22.0, 23.0, 24.0, 25.0, 26.0, 27.0
        ]
        expected_rolling_avg_30 = [
            1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 5.5, 6.0, 6.5, 7.0, 7.5, 8.0, 8.5, 9.0, 9.5, 10.0, 10.5,
            11.0, 11.5, 12.0, 12.5, 13.0, 13.5, 14.0, 14.5, 15.0, 15.5
        ]

        # Average values are calculated for indicated windows for product A
        actual_rolling_avg_7 = df[df['product_id'] == 'A']['quantity_rolling_avg_7'].tolist()
        self.assertEqual(actual_rolling_avg_7, expected_rolling_avg_7)
        actual_rolling_avg_30 = df[df['product_id'] == 'A']['quantity_rolling_avg_30'].tolist()
        self.assertEqual(actual_rolling_avg_30, expected_rolling_avg_30)

        # Average values are calculated for indicated windows for product B
        actual_rolling_avg_7 = df[df['product_id'] == 'B']['quantity_rolling_avg_7'].tolist()
        self.assertEqual(actual_rolling_avg_7, expected_rolling_avg_7)
        actual_rolling_avg_30 = df[df['product_id'] == 'B']['quantity_rolling_avg_30'].tolist()
        self.assertEqual(actual_rolling_avg_30, expected_rolling_avg_30)

    def test_remove_historical_data_records(self):
        """
        Test the removal of historical data based on the presence of the column 'quantity'.
        """
        # Invoke method from the class
        cleaned_data = self.layer.remove_historical_data_records(self.data)

        # Check that no days remain because they all have the quantity column
        self.assertEqual(len(cleaned_data), 0)

        # Drop quantity
        test_data = self.data.copy()
        test_data.drop(columns=['quantity'])

        # Check that all records remain because there is no quantity column
        self.assertEqual(len(test_data), 60)

if __name__ == '__main__':
    unittest.main()
