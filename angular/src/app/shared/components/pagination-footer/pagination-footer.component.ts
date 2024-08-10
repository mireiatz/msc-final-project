import { Component, EventEmitter, Input, OnChanges, OnInit, Output } from '@angular/core';
import { Pagination } from "../../services/api/models/pagination";

@Component({
  selector: 'app-pagination-footer',
  templateUrl: './pagination-footer.component.html',
  styleUrls: ['./pagination-footer.component.scss'],
})
export class PaginationFooterComponent implements OnInit, OnChanges {

  @Input() pagination: Pagination = {
    count: 0,
    total_items: 0,
    items_per_page: 15,
    current_page: 1,
    total_pages: 0
  };
  @Output() pageChange = new EventEmitter<number>();

  public pages: number[] = [];

  public ngOnInit() {
    this.pages = this.generatePagesArray();
  }

  public ngOnChanges(): void {
    this.pages = this.generatePagesArray();
  }

  public generatePagesArray(): number[] {
    const pages = [];

    if (this.pagination.total_pages <= 3) {
      for (let i = 1; i <= this.pagination.total_pages; i++) {
        pages.push(i);
      }
    } else {
      if (this.pagination.current_page === 1) {
        pages.push(1, 2, 3);
      } else if (this.pagination.current_page === this.pagination.total_pages) {
        pages.push(this.pagination.total_pages - 2, this.pagination.total_pages - 1, this.pagination.total_pages);
      } else {
        pages.push(this.pagination.current_page - 1, this.pagination.current_page, this.pagination.current_page + 1);
      }
    }

    return pages;
  }

  public goToPage(page: number): void {
    if (page >= 1 && page <= this.pagination.total_pages) {
      this.pageChange.emit(page);
    }
  }
}
