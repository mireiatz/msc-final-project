import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { Option } from "../../../../shared/interfaces";
import { ReorderSuggestion } from "../../../../shared/services/api/models/reorder-suggestion";

@Component({
  selector: 'page-reordering',
  templateUrl: './reordering.page.html',
  styleUrls: ['./reordering.page.scss'],
})

export class ReorderingPage  implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public errors: string[] = [];

  public reorderSuggestions: ReorderSuggestion[] = [];
  public categoryId: string | undefined = '';
  public categories: Option[] = [];
  public providerId: string | undefined = '';
  public providers: Option[] = [];

  public columns = [
    { header: 'Product Name', field: 'product_name' },
    { header: 'Unit', field: 'unit' },
    { header: 'Amount Per Unit', field: 'amount_per_unit' },
    { header: 'Cost Per Unit', field: 'cost_per_unit' },
    { header: 'Stock Balance', field: 'stock_balance' },
    { header: 'Predicted Demand', field: 'predicted_demand' },
    { header: 'Safety Stock', field: 'safety_stock' },
    { header: 'Reorder Amount', field: 'reorder_amount' },
    { header: 'Total Cost', field: 'total_cost' }
  ];

  public page = 1;
  public pagination = {
    count: 0,
    total_items: 0,
    items_per_page: 15,
    current_page: 1,
    total_pages: 0
  };

  constructor(
    protected apiService: ApiService,
  ) {
    this.fetchProviders();
    this.fetchCategories();
  }


  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getReorderSuggestions(page: number) {
    if(!this.providerId || !this.categoryId) return;

    this.isLoading = true;

    this.apiService.getReorderSuggestions({
      providerId: this.providerId,
      categoryId: this.categoryId,
      page: page,
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          this.reorderSuggestions = response.data.items;
          this.pagination = response.data.pagination;
          console.log(this.pagination)
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public onCategorySelection(selectedCategory: any) {
    this.categoryId = selectedCategory;
    this.getReorderSuggestions(this.page);
  }

  public onProviderSelection(selectedProvider: any) {
    this.providerId = selectedProvider;
    console.log(this.providerId)
    this.getReorderSuggestions(this.page);
  }


  public fetchProviders() {
    this.apiService.getProviders().pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;
          this.providers = response.data.map(provider => ({
            id: provider.id,
            name: provider.name
          }));
          if(this.providers) {
            this.onProviderSelection(this.providers[0].id);
            this.getReorderSuggestions(this.page);
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

  public fetchCategories() {
    this.apiService.getCategories().pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;

          this.categories = response.data.map(category => ({
            id: category.id,
            name: category.name
          }));

          if(this.categories) {
            this.onCategorySelection(this.categories[0].id)
            this.getReorderSuggestions(this.page);
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

  public onPageChange(page: number): void {
    this.page = page;
    this.pagination.current_page = page;
    this.getReorderSuggestions(this.page);
  }
}
