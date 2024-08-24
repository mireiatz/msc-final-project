import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import {
  format,
  startOfDay,
  startOfMonth,
  startOfWeek,
  startOfYear
} from "date-fns";

@Component({
  selector: 'app-date-range-picker',
  templateUrl: './date-range-picker.component.html',
  styleUrls: ['./date-range-picker.component.scss'],
})
export class DateRangePickerComponent implements OnInit {

  @Output() datesSelected = new EventEmitter<{ startDate: string, endDate: string }>();

  public calendarStartDate: string = '';
  public calendarEndDate: string = '';
  public startDate: string = '';
  public endDate: string = '';
  public period: string = 'month';

  ngOnInit(): void {
    this.setPeriod(this.period);
  }

  public setPeriod(period: string): void {
    this.period = period;
    const [start, end] = this.determineDates(period);
    this.startDate = start;
    this.endDate = end;
    this.calendarStartDate = format(start, 'yyyy-MM-dd');
    this.calendarEndDate = format(end, 'yyyy-MM-dd');
    this.selectDates();
  }

  private determineDates(period: string): [string, string] {
    const now = new Date();
    let startDate: Date;

    switch (period) {
      case 'day':
        startDate = startOfDay(now);
        break;
      case 'week':
        startDate = startOfWeek(now, { weekStartsOn: 1 });
        break;
      case 'month':
        startDate = startOfMonth(now);
        break;
      case 'year':
        startDate = startOfYear(now);
        break;
      default:
        throw new Error(`Invalid period: ${period}`);
    }

    return [startDate.toString(), now.toString()];
  }

  public onDateSelected() {
    this.period = '';
    this.selectDates();
  }

  public selectDates() {
    this.startDate = this.calendarStartDate;
    this.endDate = this.calendarEndDate;
    const formattedStartDate = format(startOfDay(this.startDate), 'yyyy-MM-dd HH:mm:ss');
    const formattedEndDate = format(this.endDate, 'yyyy-MM-dd HH:mm:ss');
    this.datesSelected.emit({ startDate: formattedStartDate, endDate: formattedEndDate });
  }
}
