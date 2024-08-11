import { Component, OnDestroy } from "@angular/core";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { OverviewMetrics } from "../../../../shared/services/api/models/overview-metrics";

@Component({
	selector: 'page-overview',
	templateUrl: './overview.page.html',
	styleUrls: ['./overview.page.scss'],
})

export class OverviewPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public errors: string[] = [];
  public metrics: OverviewMetrics | undefined = undefined;
  public startDate: string = '';
  public endDate: string = '';

  constructor(
    protected apiService: ApiService
  ) {}

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getOverviewMetrics() {
    this.apiService.getOverviewMetrics({
      body: {
        start_date: this.startDate,
        end_date: this.endDate,
      }
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
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

  public setDatesSelected(event: any) {
    this.startDate = event.startDate;
    this.endDate = event.endDate;
    this.getOverviewMetrics();
  }
}
