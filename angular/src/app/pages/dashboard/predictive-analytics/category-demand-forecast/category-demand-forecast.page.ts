import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { HttpErrorResponse } from "@angular/common/http";
import { Option } from "../../../../shared/interfaces";
import { ModalService } from "../../../../shared/services/modal/modal.service";
import { ProductDemandForecastModalComponent } from "../modals/product-demand-forecast-modal/product-demand-forecast-modal.component";
import { ItemDemand } from "../../../../shared/services/api/models/item-demand";
import { CategoryDemand } from "../../../../shared/services/api/models/category-demand";

@Component({
  selector: 'page-category-demand-forecast',
  templateUrl: './category-demand-forecast.page.html',
  styleUrls: ['./category-demand-forecast.page.scss'],
})

export class CategoryDemandForecastPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public errors: string[] = [];

  public categoryDemand: CategoryDemand | null = null;
  public categoryId: string | undefined = '';
  public forecastData: any[] = [];
  public categories: Option[] = [];

  constructor(
    protected apiService: ApiService,
    protected modalService: ModalService,
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

          this.categories = response.data.map(category => ({
            id: category.id,
            name: category.name
          }));

          if(this.categories) {
            this.onCategorySelection(this.categories[0].id)
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

    this.apiService.getCategoryDemandForecast({
      categoryId: this.categoryId,
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;
          this.categoryDemand = response.data;
          this.mapForecastData(response.data.products);
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public mapForecastData(demands: ItemDemand[]) {
    this.forecastData = demands.map(product => {
      return {
        name: product.name,
        series: product.predictions.map(prediction => ({
          product_id: product.id,
          name: prediction.date,
          value: +prediction.value
        }))
      };
    });
  }

  public onProductSelection(data: any) {
    if (!this.categoryDemand) return;

    const selectedProduct = this.categoryDemand.products.filter(product => product.id === data.product_id);

    this.modalService.open(ProductDemandForecastModalComponent, {
      title: `Demand Forecast for ${selectedProduct[0].name}`,
      product_name: selectedProduct[0].name,
      predictions: selectedProduct[0].predictions
    });
  }
}
