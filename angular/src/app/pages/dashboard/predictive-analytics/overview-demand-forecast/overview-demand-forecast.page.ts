import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { HttpErrorResponse } from "@angular/common/http";
import { OverviewDemandForecast } from "../../../../shared/services/api/models/overview-demand-forecast";

@Component({
  selector: 'page-overview-demand-forecast',
  templateUrl: './overview-demand-forecast.page.html',
  styleUrls: ['./overview-demand-forecast.page.scss'],
})

export class OverviewDemandForecastPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public errors: string[] = [];

  public forecastData: any[] = [];
  public categories: string[] = [];

  constructor(
    protected apiService: ApiService,
  ) {
    this.getDemandForecast()
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getDemandForecast() {
    this.isLoading = true;

    this.apiService.getOverviewDemandForecast().pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;
          this.mapForecastData(response.data);

        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public mapForecastData(data: OverviewDemandForecast[]){
    this.forecastData = data.map(overviewData => {
      return {
        name: overviewData.category,
        series: overviewData.predictions.map(prediction => ({
          name: prediction.date,
          value: +prediction.value
        }))
      };
    });
  }
}
