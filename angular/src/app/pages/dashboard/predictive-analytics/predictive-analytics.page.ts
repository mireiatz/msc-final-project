import { Component, OnDestroy } from "@angular/core";
import { Subject } from "rxjs";

@Component({
	selector: 'page-predictive-analytics',
	templateUrl: './predictive-analytics.page.html',
	styleUrls: ['./predictive-analytics.page.scss'],
})

export class PredictiveAnalyticsPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public tabs = [
    {
      label: 'Category-level',
      route: 'category-level',
      key: 'category-level',
    },
    {
      label: 'Product-level',
      route: 'product-level',
      key: 'product-level',
    },
    {
      label: 'Weekly',
      route: 'weekly',
      key: 'weekly',
    },
    {
      label: 'Month',
      route: 'month',
      key: 'month',
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
