import { Component, OnDestroy } from "@angular/core";
import { Subject } from "rxjs";

@Component({
	selector: 'page-descriptive-analytics',
	templateUrl: './descriptive-analytics.page.html',
	styleUrls: ['./descriptive-analytics.page.scss'],
})

export class DescriptiveAnalyticsPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
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
  public activeTab: string = '';

  constructor() {
    this.activeTab = window.location.href.split('/').pop() ?? '';
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }
}
