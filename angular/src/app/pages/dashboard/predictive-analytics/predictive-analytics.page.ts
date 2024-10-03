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
      label: 'Overview',
      route: 'overview',
      key: 'overview',
    },
    {
      label: 'Category based',
      route: 'category-based',
      key: 'category-based',
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
