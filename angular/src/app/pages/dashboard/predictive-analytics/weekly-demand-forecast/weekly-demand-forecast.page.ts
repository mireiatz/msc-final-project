import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { HttpErrorResponse } from "@angular/common/http";
import { WeeklyDemand } from "../../../../shared/services/api/models/weekly-demand";

@Component({
  selector: 'page-weekly-demand-forecast',
  templateUrl: './weekly-demand-forecast.page.html',
  styleUrls: ['./weekly-demand-forecast.page.scss'],
})

export class WeeklyDemandForecastPage implements OnDestroy {

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

    this.apiService.getWeeklyAggregatedDemandForecast().pipe(
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

  public mapForecastData(data: WeeklyDemand[]){
    this.forecastData = data.map(category => {
      return {
        name: category.name,
        series: category.weeks.map(week => ({
          name: week.name,
          value: +week.value
        }))
      };
    });
  }

}
