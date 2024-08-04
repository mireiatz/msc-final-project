import { Component } from "@angular/core";

@Component({
	selector: 'page-descriptive-analytics',
	templateUrl: './descriptive-analytics.page.html',
	styleUrls: ['./descriptive-analytics.page.scss'],
})

export class DescriptiveAnalyticsPage {

  public tabs = [
    {
      label: 'Overview',
      route: 'overview',
      key: 'overview',
    },
    {
      label: 'Product Performance',
      route: 'product-performance',
      key: 'product-performance',
    },
    {
      label: 'Sales',
      route: 'sales',
      key: 'sales',
    },
    {
      label: 'Stock Levels',
      route: 'stock-levels',
      key: 'stock-levels',
    },
  ];

  constructor() {}
}
