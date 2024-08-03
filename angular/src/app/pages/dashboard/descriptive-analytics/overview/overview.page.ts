import { Component } from "@angular/core";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { take } from "rxjs";
import { Category } from "../../../../shared/services/api/models/category";
import { HttpErrorResponse } from "@angular/common/http";

@Component({
	selector: 'page-overview',
	templateUrl: './overview.page.html',
	styleUrls: ['./overview.page.scss'],
})

export class OverviewPage {

  public categories: Category[] | undefined = undefined;
  public errors: string[] = [];

  constructor(
    protected apiService: ApiService
  ) {
    this.getCategories();
  }

  public getCategories() {
    this.apiService.getCategories().pipe(
      take(1)
    ).subscribe({
        next: response => {
          this.categories = response.data;
          console.log(this.categories)
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }
}
