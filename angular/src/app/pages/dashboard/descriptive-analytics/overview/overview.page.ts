import { Component } from "@angular/core";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { OverviewMetrics } from "../../../../shared/services/api/models/overview-metrics";

@Component({
	selector: 'page-overview',
	templateUrl: './overview.page.html',
	styleUrls: ['./overview.page.scss'],
})

export class OverviewPage {

  public metrics: OverviewMetrics | undefined = undefined;
  public period: string = 'day';
  public errors: string[] = [];

  constructor(
    protected apiService: ApiService
  ) {
    this.getOverviewMetrics();
  }

  public getOverviewMetrics() {
    this.apiService.getOverviewMetrics({
      body: {
        period: this.period,
      }
    }).pipe(
      take(1)
    ).subscribe({
        next: response => {
          this.metrics = response.data;
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public setPeriod(period: string){
    this.period = period;
    this.getOverviewMetrics();
  }
}
