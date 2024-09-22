import unittest
from unittest.mock import patch
import pandas as pd
import numpy as np
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer

class TestFeatureEngineeringLayer(unittest.TestCase):

    def setUp(self):
        """
        Set up test FeatureEngineeringLayer.
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
            'year': (
                [d.year for d in product_1_month_9] +
                [d.year for d in product_2_month_8] +
                [d.year for d in product_1_month_1] +
                [d.year for d in product_2_month_3]
            ),
            'month': (
                [d.month for d in product_1_month_9] +
                [d.month for d in product_2_month_8] +
                [d.month for d in product_1_month_1] +
                [d.month for d in product_2_month_3]
            ),
            'day_of_month': (
                [d.day for d in product_1_month_9] +
                [d.day for d in product_2_month_8] +
                [d.day for d in product_1_month_1] +
                [d.day for d in product_2_month_3]
            ),
            'quantity': values_16_31 + values_16_31 + values_1_15 + values_1_15,
            'value': values_16_31 + values_16_31 + values_1_15 + values_1_15,
        })

        # Initialise the layer
        self.layer = FeatureEngineeringLayer(self.data)

    @patch.object(FeatureEngineeringLayer, 'load_mapping')
    @patch.object(FeatureEngineeringLayer, 'save_mapping')
    def test_encode_categorical_features(self, mock_save_mapping, mock_load_mapping):
        """
        Test that features are properly encoded using label encoding.
        """
        # Sample data for label encoding
        test_data = pd.DataFrame({
            'product_id': ['product_1', 'product_2', 'product_1', 'product_3', 'product_2'],
            'category': ['pet_food', 'homebaking', 'pet_food', 'general_grocery', 'homebaking']
        })

        # Mock the 'load_mapping' method to return a mapping for 'category'
        mock_load_mapping.return_value = {'pet_food': 1, 'general_grocery': 2}

        # Invoke method from the class, for the 'category' feature
        df = self.layer.encode_categorical_features(test_data, 'category')

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
        df = self.layer.encode_categorical_features(test_data, 'product_id')

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

    def test_extract_date_features(self):
        """
        Test the extraction of date-related features, including cyclic encodings.
        """
        # Test cases for date features
        test_cases = [
            {
                'day_name': 'monday', 'week': 41, 'year': 2021,
                'expected': {'date': '2021-10-11', 'day_offset': 0, 'day_of_month': 11, 'month': 10, 'month_sin': -0.87, 'month_cos': 0.5, 'weekday_sin': 0.0, 'weekday_cos': 1.0}
            },
            {
                'day_name': 'tuesday', 'week': 3, 'year': 2022,
                'expected': {'date': '2022-01-18', 'day_offset': 1, 'day_of_month': 18, 'month': 1, 'month_sin': 0.5, 'month_cos': 0.87, 'weekday_sin': 0.78, 'weekday_cos': 0.62}
            },
            {
                'day_name': 'wednesday', 'week': 12, 'year': 2022,
                'expected': {'date': '2022-03-23', 'day_offset': 2, 'day_of_month': 23, 'month': 3, 'month_sin': 1.0, 'month_cos': 0.0, 'weekday_sin': 0.97, 'weekday_cos': -0.22}
            },
            {
                'day_name': 'thursday', 'week': 38, 'year': 2023,
                'expected': {'date': '2023-09-21', 'day_offset': 3, 'day_of_month': 21, 'month': 9, 'month_sin': -1.0, 'month_cos': -0.00, 'weekday_sin': 0.43, 'weekday_cos': -0.9}
            },
            {
                'day_name': 'friday', 'week': 52, 'year': 2023,
                'expected': {'date': '2023-12-29', 'day_offset': 4, 'day_of_month': 29, 'month': 12, 'month_sin': -0.0, 'month_cos':  1.0, 'weekday_sin': -0.43, 'weekday_cos': -0.9}
            },
            {
                'day_name': 'saturday', 'week': 1, 'year': 2024,
                'expected': {'date': '2024-01-06', 'day_offset': 5, 'day_of_month': 6, 'month': 1, 'month_sin': 0.5, 'month_cos': 0.87, 'weekday_sin': -0.97, 'weekday_cos': -0.22}
            },
            {
                'day_name': 'sunday', 'week': 22, 'year': 2024,
                'expected': {'date': '2024-06-02', 'day_offset': 6, 'day_of_month': 2, 'month': 6, 'month_sin': 0.0, 'month_cos': -1.0, 'weekday_sin': -0.78, 'weekday_cos': 0.62}
            }
        ]

        # Tolerance value to account for floating-point differences
        tolerance = 0.01

        # Invoke the method from the class for each test case
        for case in test_cases:
            day_name = case['day_name']
            week = case['week']
            year = case['year']

            date, day_offset, day_of_month, month, month_sin, month_cos, weekday_sin, weekday_cos = self.layer.extract_date_features(year, week, day_name)

            # Check date features
            self.assertEqual(date.strftime('%Y-%m-%d'), case['expected']['date'], f"Date mismatch: {date} != {case['expected']['date']}")
            self.assertEqual(day_offset, case['expected']['day_offset'], f"Day offset mismatch: {day_offset} != {case['expected']['day_offset']}")
            self.assertEqual(day_of_month, case['expected']['day_of_month'], f"Day of month mismatch: {day_of_month} != {case['expected']['day_of_month']}")
            self.assertEqual(month, case['expected']['month'], f"Month mismatch: {month} != {case['expected']['month']}")

            # Use np.isclose with assertTrue for floating-point comparisons
            self.assertTrue(np.isclose(month_sin, case['expected']['month_sin'], atol=tolerance), f"Month sine mismatch: {month_sin} != {case['expected']['month_sin']}")
            self.assertTrue(np.isclose(month_cos, case['expected']['month_cos'], atol=tolerance), f"Month cosine mismatch: {month_cos} != {case['expected']['month_cos']}")
            self.assertTrue(np.isclose(weekday_sin, case['expected']['weekday_sin'], atol=tolerance), f"Weekday sine mismatch: {weekday_sin} != {case['expected']['weekday_sin']}")
            self.assertTrue(np.isclose(weekday_cos, case['expected']['weekday_cos'], atol=tolerance), f"Weekday cosine mismatch: {weekday_cos} != {case['expected']['weekday_cos']}")

    def test_pivot_weekly_data(self):
        """
        Test weekly data pivoting to daily format.
        """
        # Weekly data
        test_data = pd.DataFrame({
            'product_id': ['1234', '1111'],
            'product_id_encoded': [0, 1],
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
        df['date'] = pd.to_datetime(df['date']).dt.strftime('%Y-%m-%d')  # Convert 'date' column to strings

        # Daily data
        expected_data = pd.DataFrame({
            'quantity': [10, 40, 60, 0, 10, 3, 90, 30, 50, 70, 50, 0, 9, 100],
            'per_item_value': [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0],
            'in_stock': [1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0],
            'date': ['2022-03-07', '2022-03-08', '2022-03-09', '2022-03-10', '2022-03-11', '2022-03-12', '2022-03-13', '2021-05-31', '2021-06-01', '2021-06-02', '2021-06-03', '2021-06-04', '2021-06-05', '2021-06-06'],
            'day_offset': [0, 1, 2, 3, 4, 5, 6, 0, 1, 2, 3, 4, 5, 6],
            'day_of_month': [7, 8, 9, 10, 11, 12, 13, 31, 1, 2, 3, 4, 5, 6],
            'year': [2022, 2022, 2022, 2022, 2022, 2022, 2022, 2021, 2021, 2021, 2021, 2021, 2021, 2021],
            'month': [3, 3, 3, 3, 3, 3, 3, 5, 6, 6, 6, 6, 6, 6],
        })

        # 7 instances per product for product detailing columns
        self.assertEqual(df['product_id'].value_counts().tolist(), [7, 7], "'product_id' should have 7 instances of '1234' and '1111'")
        self.assertEqual(df['product_name'].value_counts().tolist(), [7, 7], "'product_name' should have 7 instances of 'Product A' and 'Product B'")
        self.assertEqual(df['category'].value_counts().tolist(), [7, 7], "'category' should have 7 instances of 'cat_1' and 'cat_2'")
        self.assertEqual(df['category_encoded'].value_counts().tolist(), [7, 7], "'category_encoded' should have 7 instances of '0' and '1'")
        self.assertEqual(df['in_stock'].value_counts().tolist(), [7, 7], "'in_stock' should have 7 instances of '1' and '0'")

        # Daily values from weekly data
        self.assertEqual(df['quantity'].tolist(), expected_data['quantity'].tolist(), "Mismatch in 'quantity' values")
        self.assertEqual(df['per_item_value'].tolist(), expected_data['per_item_value'].tolist(), "Mismatch in 'per_item_value'")
        self.assertEqual(df['date'].tolist(), expected_data['date'].tolist(), "Mismatch in 'date' values")
        self.assertEqual(df['day_offset'].tolist(), expected_data['day_offset'].tolist(), "Mismatch in 'day_offset' values")
        self.assertEqual(df['day_of_month'].tolist(), expected_data['day_of_month'].tolist(), "Mismatch in 'day_of_month' values")
        self.assertEqual(df['year'].tolist(), expected_data['year'].tolist(), "Mismatch in 'year' values")
        self.assertEqual(df['month'].tolist(), expected_data['month'].tolist(), "Mismatch in 'month' values")

    def test_adjust_in_stock_features(self):
        """
        Test the adjustment of stock data.
        """
        # Sample data with 28 days of 'in_stock' and 'quantity' values
        test_data = pd.DataFrame({
            'product_id': ['1111'] * 14 + ['1234'] * 14,
            'year': [2021] * 14 + [2022] * 14,
            'month': [1] * 14 + [1] * 14,
            'day_of_month': [1] * 14 + [1] * 14,
            'quantity': [0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 10, 10, 0, 0, 0, 0],
            'in_stock': [0] * 7 + [1] * 14 + [0] * 7,
        })

        # Invoke method from the class
        df = self.layer.adjust_in_stock_features(test_data)

        # 'in_stock' column shifts 7 days and is adjusted based on sales quantity
        expected_data = [0, 1, 1, 0, 1, 1, 0, 1, 1, 0, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1]
        self.assertEqual(df['in_stock'].tolist(), expected_data)

    def test_add_holiday_flag(self):
        """
        Test the holiday flag is set for known holiday periods.
        """
        # Sample data with months and days for holiday and non-holiday dates
        test_data = pd.DataFrame({
            'month': [
                7, 10, 12, 4,  # Holidays
                7, 12  # Non-holidays
            ],
            'day_of_month': [
                26, 28, 24, 1,
                25, 26
            ]
        })

        # Invoke method from the class
        df = self.layer.add_holiday_flag(test_data)

        # Holiday flags are 1 for holidays, and 0 for non-holidays
        expected_data = [1, 1, 1, 1, 0, 0]
        self.assertEqual(df['is_holiday'].tolist(), expected_data)

    def test_sort_by_columns(self):
        """
        Test sorting dataframe by columns.
        """
        # Sample data with columns that are typically used to order data
        test_data = pd.DataFrame({
            'product_id': ['Product B', 'Product B', 'Product E', 'Product A', 'Product C'],
            'month': [8, 3, 6, 1, 12],
            'day_of_month': [26, 12, 3, 1, 30],
            'year': [2022, 2024, 2022, 2021, 2023]
        })

        # Invoke method from the class
        df = self.layer.sort_by_columns(test_data, ['product_id', 'year', 'month', 'day_of_month'])

        # Sorted first by 'product_id' and then date
        expected_data = pd.DataFrame({
            'product_id': ['Product A', 'Product B', 'Product B', 'Product C', 'Product E'],
            'month': [1, 8, 3, 12, 6],
            'day_of_month': [1, 26, 12, 30, 3],
            'year': [2021, 2022, 2024, 2023, 2022]
        })
        pd.testing.assert_frame_equal(
            expected_data.reset_index(drop=True),
            df.reset_index(drop=True)
        )

        # Invoke method from the class
        df = self.layer.sort_by_columns(df)

        # Sorted by date only
        expected_data = pd.DataFrame({
            'product_id': ['Product A', 'Product E', 'Product B', 'Product C', 'Product B'],
            'month': [1, 6, 8, 12, 3],
            'day_of_month': [1, 3, 26, 30, 12],
            'year': [2021, 2022, 2022, 2023, 2024]
        })
        pd.testing.assert_frame_equal(
            expected_data.reset_index(drop=True),
            df.reset_index(drop=True)
        )

    def test_create_lag_features(self):
        """
        Test the creation of lag columns for different lag periods.
        """
        # Invoke method from the class
        df = self.layer.create_lag_features(self.data, 'quantity', [1, 5, 10, 15, 30])

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

    def test_create_rolling_avg_features(self):
        """
        Test the creation of rolling average columns for different window sizes.
        """
        # Invoke method from the class
        df = self.layer.create_rolling_avg_features(self.data, 'quantity', [7, 30])

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

    def test_cyclic_encoding(self):
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

if __name__ == '__main__':
    unittest.main()
