import unittest
from unittest.mock import patch
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer

class TestFeatureEngineeringLayer(unittest.TestCase):

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

        # Mock data for historical and new data
        self.historical_data = self.create_mock_historical_data()
        self.new_data = self.create_mock_new_data()

        # Initialise the layer
        self.layer = FeatureEngineeringLayer(self.data)

    @patch.object(FeatureEngineeringLayer, 'load_mapping')
    @patch.object(FeatureEngineeringLayer, 'save_mapping')
    def test_encode_categorical_feature(self, mock_save_mapping, mock_load_mapping):
        """
        Test that categorical features are properly encoded using label encoding.
        """
        # Sample data for label encoding
        test_data = pd.DataFrame({
            'product_id': ['product_1', 'product_2', 'product_1', 'product_3', 'product_2'],
            'category': ['pet_food', 'homebaking', 'pet_food', 'general_grocery', 'homebaking']
        })

        # Mock the 'load_mapping' method to return a mapping for 'category'
        mock_load_mapping.return_value = {'pet_food': 1, 'general_grocery': 2}

        # Invoke method from the class, for the 'category' feature
        df = self.layer.encode_categorical_feature(test_data, 'category')

        # Label encoding was applied
        self.assertIn('category_encoded', df.columns)  # 'category_encoded' exists
        self.assertTrue(pd.api.types.is_integer_dtype(df['category_encoded']))  # The categories are integers

        # Previously encoded categories persist and new ones are mapped
        expected_encodings = [1, 3, 1, 2, 3]
        self.assertEqual(df['category_encoded'].tolist(), expected_encodings)

        # Assert that 'save_mapping' was called to update the mapping with 'homebaking'
        mock_save_mapping.assert_called_once()

        # 'general_grocery' was added to the mapping with the next code (3)
        args, kwargs = mock_save_mapping.call_args
        updated_mapping = args[1]  # The mapping passed to 'save_mapping'
        self.assertEqual(updated_mapping['homebaking'], 3)

        # Mock the 'load_mapping' method to return a mapping for 'product_id'
        mock_load_mapping.return_value = {'product_2': 1, 'product_3': 3}

        # Invoke method from the class, for the 'product_id' feature
        df = self.layer.encode_categorical_feature(test_data, 'product_id')

        # Label encoding was applied
        self.assertIn('product_id_encoded', df.columns)  # 'product_id_encoded' exists
        self.assertTrue(pd.api.types.is_integer_dtype(df['product_id_encoded']))  # The product IDs are integers

        # Previously encoded product IDs persist and new ones are mapped
        expected_encodings = [4, 1, 4, 3, 1]
        self.assertEqual(df['product_id_encoded'].tolist(), expected_encodings)

        # 'product_1' was added to the mapping with the next code (4)
        args, kwargs = mock_save_mapping.call_args
        updated_mapping = args[1]  # The mapping passed to 'save_mapping'
        self.assertEqual(updated_mapping['product_1'], 4)

    def test_pivot_weekly_data(self):
        """
        Test weekly data pivoting to daily format.
        """
        # Sample weekly data
        test_data = pd.DataFrame({
            'product_id': ['1234', '1111'],
            'product_id_encoded': [0, 1],
            'original_product_id': ['1234', '1111'],
            'product_name': ['Product A', 'Product B'],
            'category': ['cat_1', 'cat_2'],
            'category_encoded': [0, 1],
            'monday': [10, 30],
            'tuesday': [40, 50],
            'wednesday': [60, 70],
            'thursday': [0, 50],
            'friday': [10, 0],
            'saturday': [3, 9],
            'sunday': [90, 100],
            'quantity': [213, 309],
            'value': [213.0, 618.0],
            'in_stock': [1, 0],
            'year': [2022, 2021],
            'week': [10, 22]
        })

        # Invoke method from the class
        df = self.layer.pivot_weekly_data(test_data)

        # 7 instances per product for product detailing columns
        self.assertEqual(df['product_id'].value_counts().tolist(), [7, 7], "'product_id' should have 7 instances of '1234' and '1111'")
        self.assertEqual(df['product_name'].value_counts().tolist(), [7, 7], "'product_name' should have 7 instances of 'Product A' and 'Product B'")
        self.assertEqual(df['category'].value_counts().tolist(), [7, 7], "'category' should have 7 instances of 'cat_1' and 'cat_2'")
        self.assertEqual(df['category_encoded'].value_counts().tolist(), [7, 7], "'category_encoded' should have 7 instances of '0' and '1'")
        self.assertEqual(df['in_stock'].value_counts().tolist(), [7, 7], "'in_stock' should have 7 instances of '1' and '0'")

        # Daily values from weekly data
        expected_data = pd.DataFrame({
            'quantity': [10, 40, 60, 0, 10, 3, 90, 30, 50, 70, 50, 0, 9, 100],
            'per_item_value': [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0],
            'in_stock': [1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0],
            'weekday': ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'year': [2022, 2022, 2022, 2022, 2022, 2022, 2022, 2021, 2021, 2021, 2021, 2021, 2021, 2021],
            'week': [10, 10, 10, 10, 10, 10, 10, 22, 22, 22, 22, 22, 22, 22,]
        })
        self.assertEqual(df['quantity'].tolist(), expected_data['quantity'].tolist(), "Mismatch in 'quantity' values")
        self.assertEqual(df['per_item_value'].tolist(), expected_data['per_item_value'].tolist(), "Mismatch in 'per_item_value'")
        self.assertEqual(df['weekday'].tolist(), expected_data['weekday'].tolist(), "Mismatch in 'weekday' values")
        self.assertEqual(df['year'].tolist(), expected_data['year'].tolist(), "Mismatch in 'year' values")
        self.assertEqual(df['week'].tolist(), expected_data['week'].tolist(), "Mismatch in 'month' values")

    def test_cyclic_encoding(self):
        """
        Test cyclic encoding of periodic features.
        """
        # Test cases for cyclic encoding
        test_cases = [
            (0, 7, 0.0, 1.0),  # Weekday example, 0th day of the week (Sunday)
            (3, 7, 0.43, -0.9),  # Midweek (Wednesday, assuming 0-based indexing)
            (6, 7, -0.78, 0.62),  # Last day of the week (Saturday)
            (0, 12, 0.0, 1.0),  # Month example, first month (January)
            (6, 12, 0.0, -1.0),  # Midyear (July)
            (11, 12, -0.5, 0.87)  # Last month (December)
        ]

        # Invoke method from the class
        for value, max_value, expected_sin, expected_cos in test_cases:
            # Get the cyclic encoding values
            sin_value, cos_value = self.layer.cyclic_encoding(value, max_value)

            # sin and cos values are correct
            self.assertEqual(sin_value, expected_sin)
            self.assertEqual(cos_value, expected_cos)


    def test_create_date_feature(self):
        """
        Test the creation of dates.
        """
        # Sample data for date creation
        test_df = pd.DataFrame({
            'year': [2022, 2023],
            'week': [1, 10],
            'weekday': [0, 2]  # Monday, Wednesday
        })

        # Invoke method from the class
        result_df = self.layer.create_date_feature(test_df)

        # Assert that dates are correct
        expected_dates = ['2022-01-03', '2023-03-08']
        for idx, expected_date in enumerate(expected_dates):
            self.assertEqual(result_df['date'].iloc[idx].strftime('%Y-%m-%d'), expected_date, f"Date mismatch for row {idx}")

    def test_create_week_features(self):
        """
        Test the creation of weekly features.
        """
        # Test input data
        test_data = pd.DataFrame({
            'date': ['2022-01-03', '2022-01-08', '2022-05-17'],  # Monday, Saturday, Tuesday
        })
        test_data['date'] = pd.to_datetime(test_data['date'])  # Ensure date is in datetime format

        # Invoke method from the class
        df = self.layer.create_week_features(test_data)

        # Assert weekday encoding and week number assignment
        expected_weekdays = [0, 5, 1]
        expected_weeks = [1, 1, 20]
        for idx in range(len(expected_weekdays)):
            self.assertEqual(df['weekday'].iloc[idx], expected_weekdays[idx], f"Weekday mismatch for row {idx}")
            self.assertEqual(df['week'].iloc[idx], expected_weeks[idx], f"Week number mismatch for row {idx}")

    def test_create_month_features(self):
        # Test dates
        test_data = pd.DataFrame({
            'date': ['2022-01-03', '2022-05-15', '2022-12-25']
        })
        test_data['date'] = pd.to_datetime(test_data['date'])  # Ensure date is in datetime format

        # Invoke method from the class
        df = self.layer.create_month_features(test_data)

        # Assert month and day values
        expected_months = [1, 5, 12]
        expected_days_of_month = [3, 15, 25]
        expected_month_sin = [0.50, -0.87, 0.0]
        expected_month_cos = [0.87, 0.5, 1.0]

        for idx in range(len(expected_months)):
            self.assertEqual(df['month'].iloc[idx], expected_months[idx], f"Month mismatch for row {idx}")
            self.assertEqual(df['day_of_month'].iloc[idx], expected_days_of_month[idx], f"Day of month mismatch for row {idx}")

    def test_create_year_feature(self):
        """
        Test the creation of the year feature.
        """
        # Test dates
        test_data = pd.DataFrame({
            'date': ['2021-12-13', '2022-01-03', '2023-05-15', '2024-12-25']
        })
        test_data['date'] = pd.to_datetime(test_data['date'])  # Ensure date is in datetime format

        # Invoke method from the class
        df = self.layer.create_year_feature(test_data)

        # Assert year values
        expected_months = [2021, 2022, 2023, 2024]
        for idx in range(len(expected_months)):
            self.assertEqual(df['year'].iloc[idx], expected_months[idx], f"year mismatch for row {idx}")

    def test_create_time_features(self):
        """
        Test the extraction of date-related features, including cyclic encodings.
        """
        # Test cases for date features
        test_cases = [
            {
                'day_name': 'monday', 'week': 41, 'year': 2021,
                'expected': {'date': '2021-10-11', 'weekday': 0, 'day_of_month': 11, 'month': 10, 'month_sin': -0.87, 'month_cos': 0.5, 'weekday_sin': 0.0, 'weekday_cos': 1.0}
            },
            {
                'day_name': 'tuesday', 'week': 3, 'year': 2022,
                'expected': {'date': '2022-01-18', 'weekday': 1, 'day_of_month': 18, 'month': 1, 'month_sin': 0.5, 'month_cos': 0.87, 'weekday_sin': 0.78, 'weekday_cos': 0.62}
            },
            {
                'day_name': 'wednesday', 'week': 12, 'year': 2022,
                'expected': {'date': '2022-03-23', 'weekday': 2, 'day_of_month': 23, 'month': 3, 'month_sin': 1.0, 'month_cos': 0.0, 'weekday_sin': 0.97, 'weekday_cos': -0.22}
            },
            {
                'day_name': 'thursday', 'week': 38, 'year': 2023,
                'expected': {'date': '2023-09-21', 'weekday': 3, 'day_of_month': 21, 'month': 9, 'month_sin': -1.0, 'month_cos': -0.00, 'weekday_sin': 0.43, 'weekday_cos': -0.9}
            },
            {
                'day_name': 'friday', 'week': 52, 'year': 2023,
                'expected': {'date': '2023-12-29', 'weekday': 4, 'day_of_month': 29, 'month': 12, 'month_sin': -0.0, 'month_cos':  1.0, 'weekday_sin': -0.43, 'weekday_cos': -0.9}
            },
            {
                'day_name': 'saturday', 'week': 1, 'year': 2024,
                'expected': {'date': '2024-01-06', 'weekday': 5, 'day_of_month': 6, 'month': 1, 'month_sin': 0.5, 'month_cos': 0.87, 'weekday_sin': -0.97, 'weekday_cos': -0.22}
            },
            {
                'day_name': 'sunday', 'week': 22, 'year': 2024,
                'expected': {'date': '2024-06-02', 'weekday': 6, 'day_of_month': 2, 'month': 6, 'month_sin': 0.0, 'month_cos': -1.0, 'weekday_sin': -0.78, 'weekday_cos': 0.62}
            }
        ]

        # Tolerance value to account for floating-point differences
        tolerance = 0.01

        # Convert test cases into a DataFrame
        test_df = pd.DataFrame({
            'year': [case['year'] for case in test_cases],
            'week': [case['week'] for case in test_cases],
            'weekday': [case['day_name'] for case in test_cases]
        })

        # Invoke the method from the class
        df_with_date_features = self.layer.create_time_features(test_df)

        # Check date features for each test case
        for idx, case in enumerate(test_cases):
            expected = case['expected']
            row = df_with_date_features.iloc[idx]

            # Check date
            self.assertEqual(row['date'].strftime('%Y-%m-%d'), expected['date'], f"Date mismatch: {row['date']} != {expected['date']}")
            self.assertEqual(row['weekday'], expected['weekday'], f"Day offset mismatch: {row['weekday']} != {expected['weekday']}")
            self.assertEqual(row['day_of_month'], expected['day_of_month'], f"Day of month mismatch: {row['day_of_month']} != {expected['day_of_month']}")
            self.assertEqual(row['month'], expected['month'], f"Month mismatch: {row['month']} != {expected['month']}")

            # Check cyclic encodings with tolerance
            self.assertTrue(np.isclose(row['month_sin'], expected['month_sin'], atol=tolerance), f"Month sine mismatch: {row['month_sin']} != {expected['month_sin']}")
            self.assertTrue(np.isclose(row['month_cos'], expected['month_cos'], atol=tolerance), f"Month cosine mismatch: {row['month_cos']} != {expected['month_cos']}")
            self.assertTrue(np.isclose(row['weekday_sin'], expected['weekday_sin'], atol=tolerance), f"Weekday sine mismatch: {row['weekday_sin']} != {expected['weekday_sin']}")
            self.assertTrue(np.isclose(row['weekday_cos'], expected['weekday_cos'], atol=tolerance), f"Weekday cosine mismatch: {row['weekday_cos']} != {expected['weekday_cos']}")

    def test_adjust_in_stock(self):
        """
        Test the adjustment of stock status.
        """
        # Sample data with 28 days of 'in_stock' and 'quantity' values
        test_data = pd.DataFrame({
            'product_id': ['1111'] * 14 + ['1234'] * 14,
            'date': ['2021-01-01', '2021-01-02', '2021-01-03', '2021-01-04', '2021-01-05', '2021-01-06', '2021-01-07', '2021-01-08', '2021-01-09', '2021-01-10', '2021-01-11', '2021-01-12', '2021-01-13', '2021-01-14', '2022-01-01', '2022-01-02', '2022-01-03', '2022-01-04', '2022-01-05', '2022-01-06', '2022-01-07', '2022-01-08', '2022-01-09', '2022-01-10', '2022-01-11', '2022-01-12', '2022-01-13', '2022-01-14'],
            'quantity': [0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 0, 0, 0],
            'in_stock': [0] * 7 + [1] * 14 + [0] * 7,
        })

        # Invoke method from the class
        df = self.layer.adjust_in_stock(test_data)

        # 'in_stock' column shifts 7 days and is adjusted based on sales quantity
        expected_data = [0, 1, 1, 0, 1, 1, 0, 1, 1, 0, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1]
        self.assertEqual(df['in_stock'].tolist(), expected_data)

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
        expected_lag_1 = [0] + list(range(1, 30))
        actual_lag_1 = df[df['product_id'] == 'A']['quantity_lag_1'].tolist()
        self.assertEqual(actual_lag_1, expected_lag_1)

        expected_lag_5 = [0, 0, 0, 0, 0] + list(range(1, 26))
        actual_lag_5 = df[df['product_id'] == 'A']['quantity_lag_5'].tolist()
        self.assertEqual(actual_lag_5, expected_lag_5)

        expected_lag_10 = [0] * 10 + list(range(1, 21))
        actual_lag_10 = df[df['product_id'] == 'A']['quantity_lag_10'].tolist()
        self.assertEqual(actual_lag_10, expected_lag_10)

        expected_lag_15 = [0] * 15 + list(range(1, 16))
        actual_lag_15 = df[df['product_id'] == 'A']['quantity_lag_15'].tolist()
        self.assertEqual(actual_lag_15, expected_lag_15)

        expected_lag_30 = [0] * 30
        actual_lag_30 = df[df['product_id'] == 'A']['quantity_lag_30'].tolist()
        self.assertEqual(actual_lag_30, expected_lag_30)

        # Values are shifted the specified amount of days for Product B
        expected_lag_1 = [0] + list(range(1, 30))
        actual_lag_1 = df[df['product_id'] == 'B']['quantity_lag_1'].tolist()
        self.assertEqual(actual_lag_1, expected_lag_1)

        expected_lag_5 = [0, 0, 0, 0, 0] + list(range(1, 26))
        actual_lag_5 = df[df['product_id'] == 'B']['quantity_lag_5'].tolist()
        self.assertEqual(actual_lag_5, expected_lag_5)

        expected_lag_10 = [0] * 10 + list(range(1, 21))
        actual_lag_10 = df[df['product_id'] == 'B']['quantity_lag_10'].tolist()
        self.assertEqual(actual_lag_10, expected_lag_10)

        expected_lag_15 = [0] * 15 + list(range(1, 16))
        actual_lag_15 = df[df['product_id'] == 'B']['quantity_lag_15'].tolist()
        self.assertEqual(actual_lag_15, expected_lag_15)

        expected_lag_30 = [0] * 30
        actual_lag_30 = df[df['product_id'] == 'B']['quantity_lag_30'].tolist()
        self.assertEqual(actual_lag_30, expected_lag_30)

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

    def create_mock_historical_data(self):
        """
        Sample mock historical data
        """
        dates = pd.date_range(end='2023-12-31', periods=35, freq='D')  # Last 35 days of 2023
        return pd.DataFrame({
            'date': dates,
            'quantity': range(1, 36),
            'product_id': [1] * 35
        })

    def create_mock_new_data(self):
        """
        Sample mock new data
        """
        dates = pd.date_range(start='2024-01-01', periods=10, freq='D')
        return pd.DataFrame({
            'date': dates,
            'quantity': range(1, 11),
            'product_id': [1] * 10
        })

    @patch('pandas.read_csv')
    def test_fetch_historical_data_records(self, mock_read_csv):
        # Mock the read_csv to return the historical data
        mock_read_csv.return_value = self.historical_data

        last_date = datetime.strptime('2023-12-31', '%Y-%m-%d')

        # Test the fetching of historical data
        fetched_data = self.layer.fetch_historical_data(last_date, 35)  # Should fetch 35 days of data
        self.assertEqual(len(fetched_data), 35)
        self.assertEqual(fetched_data['date'].max(), pd.Timestamp('2023-12-31'))

    @patch('pandas.read_csv')
    def test_merge_historical_data_records(self, mock_read_csv):
        # Mock the read_csv to return the historical data
        mock_read_csv.return_value = self.historical_data

        # Test the merging of historical data with new data
        merged_data = self.layer.merge_historical_data(self.new_data)
        self.assertEqual(len(merged_data), 45)  # 35 days historical + 10 days new data
        self.assertEqual(merged_data['date'].min(), pd.Timestamp('2023-11-27'))  # Oldest in historical
        self.assertEqual(merged_data['date'].max(), pd.Timestamp('2024-01-10'))  # Latest in new data

    def test_remove_historical_data_records(self):
        # Set the mock to return True
        self.layer.historical_data_fetched = True

        # Manually merge historical and new data for this test
        combined_data = pd.concat([self.historical_data, self.new_data], ignore_index=True)

        # Test the removal of historical data
        cleaned_data = self.layer.remove_historical_data(combined_data, 35)
        self.assertEqual(len(cleaned_data), 10)  # Only the 10 days of new data should remain
        self.assertEqual(cleaned_data['date'].min(), pd.Timestamp('2024-01-01'))  # Oldest in new data
        self.assertEqual(cleaned_data['date'].max(), pd.Timestamp('2024-01-10'))  # Latest in new data

if __name__ == '__main__':
    unittest.main()
