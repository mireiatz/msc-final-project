import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { HttpErrorResponse } from "@angular/common/http";
import { WeeklyDemand } from "../../../../shared/services/api/models/weekly-demand";
import { Option } from "../../../../shared/interfaces";

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
  public categoryId: string | undefined = '';
  public categoryOptions: Option[] = [];

  constructor(
    protected apiService: ApiService,
  ) {
    this.fetchCategories();
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public onCategorySelection(selectedCategory: any) {
    this.categoryId = selectedCategory;
    this.getDemandForecast();
  }

  public fetchCategories() {
    this.apiService.getCategories().pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;

          this.categoryOptions = response.data.map(category => ({
            id: category.id,
            name: category.name
          }));

          if(this.categoryOptions) {
            this.onCategorySelection(this.categoryOptions[0].id)
            this.getDemandForecast();
          }
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public getDemandForecast() {
    if(!this.categoryId) return;

    this.isLoading = true;

    this.apiService.getWeeklyAggregatedDemandForecast({
      categoryId: this.categoryId
    }).pipe(
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

  public mapForecastData(data: WeeklyDemand){
    this.forecastData = data.weeks.map(week => ({
      name: week.name,
      value: Number(week.value)
    }));
  }
}
