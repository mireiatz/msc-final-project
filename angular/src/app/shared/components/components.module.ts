import { SidebarComponent } from "./sidebar/sidebar.component";
import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { RouterModule } from "@angular/router";
import { TabsComponent } from "./tabs/tabs.component";
import { TableComponent } from "./table/table.component";
import { DateRangePickerComponent } from "./date-range-picker/date-range-picker.component";
import { FormsModule } from "@angular/forms";
import { PaginationFooterComponent } from "./pagination-footer/pagination-footer.component";
import { LoaderComponent } from "./loader/loader.component";

const COMPONENTS = [
  SidebarComponent,
  TabsComponent,
  TableComponent,
  DateRangePickerComponent,
  PaginationFooterComponent,
  LoaderComponent,
]
@NgModule({
  declarations: [
    ...COMPONENTS,
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,
  ],
  providers: [],
  exports: [
    ...COMPONENTS,
  ]
})
export class ComponentsModule {}
