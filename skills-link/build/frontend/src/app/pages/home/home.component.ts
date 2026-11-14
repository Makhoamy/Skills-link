import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { JobService } from '../../services/job.service';
import { Province } from '../../models/job.model';

const PROVINCES: Province[] = [
  { id: 1, name: 'Eastern Cape' }, { id: 2, name: 'Free State' }, { id: 3, name: 'Gauteng' },
  { id: 4, name: 'KwaZulu-Natal' }, { id: 5, name: 'Limpopo' }, { id: 6, name: 'Mpumalanga' },
  { id: 7, name: 'North West' }, { id: 8, name: 'Northern Cape' }, { id: 9, name: 'Western Cape' },
];

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './home.component.html',
  styleUrl: './home.component.css',
})
export class HomeComponent {
  keyword = '';
  provinceId: number | null = null;
  provinces = PROVINCES;
  popularJobs = ['Cleaner', 'General Worker', 'Gardener', 'Painter', 'Domestic Worker', 'Warehouse Worker', 'Security Guard', 'Kitchen Assistant'];

  constructor(private router: Router, private jobs: JobService) {}

  search(): void {
    this.router.navigate(['/jobs'], {
      queryParams: { keyword: this.keyword || null, provinceId: this.provinceId || null },
    });
  }
}
