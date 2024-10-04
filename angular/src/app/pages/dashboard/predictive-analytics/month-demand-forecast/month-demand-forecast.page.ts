import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { HttpErrorResponse } from "@angular/common/http";
import { AggregatedDemand } from "../../../../shared/services/api/models/aggregated-demand";

@Component({
  selector: 'page-month-demand-forecast',
  templateUrl: './month-demand-forecast.page.html',
  styleUrls: ['./month-demand-forecast.page.scss'],
})

export class MonthDemandForecastPage implements OnDestroy {

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

    this.apiService.getMonthAggregatedDemandForecast().pipe(
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

  public mapForecastData(data: AggregatedDemand[]){
    this.forecastData = data.map(category => {
      return {
        name: category.name,
        value: category.value
      };
    });
  }
}
