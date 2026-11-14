import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ProfileService } from '../../services/profile.service';
import { JobService } from '../../services/job.service';
import { Province } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-worker-profile',
  standalone: true,
  imports: [FormsModule, RouterLink, PageNavComponent],
  templateUrl: './worker-profile.component.html',
  styleUrl: './worker-profile.component.css',
})
export class WorkerProfileComponent implements OnInit {
  firstName = '';
  lastName = '';
  gender = '';
  dob = '';
  citizenship = '';
  driversLicense = '';
  idNumber = '';
  provinceId: number | null = null;
  city = '';
  bio = '';
  yearsExperience: number | null = null;
  availability = '';

  shareEquityInfo = false;
  ethnicity = '';
  hasDisability = '';

  provinces = signal<Province[]>([]);
  hasIdNumber = signal(false);
  hasCv = signal(false);
  hasPhoto = signal(false);
  profileExists = signal(false);

  submitted = signal(false);
  saved = signal(false);
  saving = signal(false);
  errorMessage = signal<string | null>(null);
  uploadMessage = signal<string | null>(null);

  constructor(private profileService: ProfileService, private jobService: JobService, private router: Router) {}

  ngOnInit(): void {
    this.jobService.getProvinces().subscribe((list) => this.provinces.set(list));
    this.loadProfile();
    this.loadEquity();
  }

  loadProfile(): void {
    this.profileService.getWorker().subscribe({
      next: (res) => {
        const p = res.profile;
        if (!p) return;
        this.profileExists.set(true);
        this.firstName = p.first_name ?? '';
        this.lastName = p.last_name ?? '';
        this.gender = p.gender ?? '';
        this.dob = (p.date_of_birth ?? '').substring(0, 10);
        this.citizenship = p.citizenship ?? '';
        this.driversLicense = p.drivers_license ?? '';
        this.provinceId = p.province_id !== null && p.province_id !== undefined ? Number(p.province_id) : null;
        this.city = p.city ?? '';
        this.bio = p.bio ?? '';
        this.yearsExperience = p.years_experience !== null && p.years_experience !== undefined ? Number(p.years_experience) : null;
        this.availability = p.availability ?? '';
        this.hasIdNumber.set(p.has_id_number);
        this.hasCv.set(p.has_cv);
        this.hasPhoto.set(p.has_photo);
      },
      error: () => this.errorMessage.set('Could not load your profile.'),
    });
  }

  loadEquity(): void {
    this.profileService.getConsent().subscribe({
      next: (res) => {
        if (res.equity) {
          this.shareEquityInfo = true;
          this.ethnicity = res.equity.ethnicity ?? '';
          this.hasDisability = res.equity.has_disability ?? '';
        }
      },
      error: () => {},
    });
  }

  /** Returns a map of field -> message. Empty map means the form is valid. */
  validate(): Record<string, string> {
    const e: Record<string, string> = {};
    const nameOk = /^[\p{L}][\p{L}\s'’.\-]*$/u;

    const first = (this.firstName ?? '').trim();
    if (!first) e['firstName'] = 'First name is required.';
    else if (first.length < 2) e['firstName'] = 'First name must be at least 2 characters.';
    else if (!nameOk.test(first)) e['firstName'] = 'First name contains invalid characters.';

    const last = (this.lastName ?? '').trim();
    if (!last) e['lastName'] = 'Last name is required.';
    else if (last.length < 2) e['lastName'] = 'Last name must be at least 2 characters.';
    else if (!nameOk.test(last)) e['lastName'] = 'Last name contains invalid characters.';

    if (!this.gender) e['gender'] = 'Please select your gender.';

    if (!this.dob) {
      e['dob'] = 'Date of birth is required.';
    } else {
      const d = new Date(this.dob);
      const today = new Date();
      if (isNaN(d.getTime()) || d > today) {
        e['dob'] = 'Please enter a valid date of birth.';
      } else {
        let age = today.getFullYear() - d.getFullYear();
        const m = today.getMonth() - d.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < d.getDate())) age--;
        if (age < 16) e['dob'] = 'You must be at least 16 years old.';
        else if (age > 100) e['dob'] = 'Please enter a valid date of birth.';
      }
    }

    if (!this.citizenship) e['citizenship'] = 'Please select your citizenship.';

    const id = (this.idNumber ?? '').replace(/\s+/g, '');
    if (!id) {
      if (!this.hasIdNumber()) e['idNumber'] = 'ID / passport number is required.';
    } else if (this.citizenship === 'South African') {
      if (!/^\d{13}$/.test(id)) e['idNumber'] = 'South African ID numbers have 13 digits.';
      else if (!this.luhnOk(id)) e['idNumber'] = 'That ID number is not valid.';
    } else if (!/^[A-Za-z0-9]{6,20}$/.test(id)) {
      e['idNumber'] = 'Passport/permit number must be 6-20 letters or digits.';
    }

    if ((this.driversLicense ?? '').length > 40) e['driversLicense'] = 'Driver\'s license is too long (max 40).';

    if (this.provinceId === null || this.provinceId === undefined) e['province'] = 'Please select your province.';

    const city = (this.city ?? '').trim();
    if (!city) e['city'] = 'City / town is required.';
    else if (city.length > 80) e['city'] = 'City / town is too long (max 80).';

    const yrs = this.yearsExperience as any;
    if (yrs === null || yrs === undefined || yrs === '') e['yearsExperience'] = 'Years of experience is required (use 0 if none).';
    else if (!Number.isInteger(Number(yrs)) || Number(yrs) < 0 || Number(yrs) > 60) e['yearsExperience'] = 'Enter a whole number between 0 and 60.';

    if (!this.availability) e['availability'] = 'Please select your availability.';

    if ((this.bio ?? '').length > 600) e['bio'] = 'Bio is too long (max 600 characters).';

    return e;
  }

  private luhnOk(id: string): boolean {
    let sum = 0;
    for (let i = 0; i < 13; i++) {
      let n = Number(id[i]);
      if (i % 2 === 1) { n *= 2; if (n > 9) n -= 9; }
      sum += n;
    }
    return sum % 10 === 0;
  }

  get errors(): Record<string, string> {
    return this.submitted() ? this.validate() : {};
  }

  /** Save the profile. When continueAfter is true, go on to My Adverts once it has saved. */
  save(continueAfter = false): void {
    this.errorMessage.set(null);
    this.saved.set(false);
    this.submitted.set(true);
    if (this.saving()) return;

    const problems = this.validate();
    const keys = Object.keys(problems);
    if (keys.length) {
      this.errorMessage.set('Please fix the highlighted fields before saving.');
      setTimeout(() => document.getElementById(keys[0] === 'province' ? 'province' : keys[0])?.focus(), 0);
      return;
    }

    const data: any = {
      first_name: this.firstName.trim(),
      last_name: this.lastName.trim(),
      gender: this.gender,
      date_of_birth: this.dob,
      citizenship: this.citizenship,
      drivers_license: (this.driversLicense ?? '').trim(),
      province_id: this.provinceId,
      city: this.city.trim(),
      bio: (this.bio ?? '').trim(),
      years_experience: Number(this.yearsExperience),
      availability: this.availability,
    };
    if ((this.idNumber ?? '').trim()) {
      data.id_number = this.idNumber.replace(/\s+/g, '');
    }

    this.saving.set(true);
    this.profileService.saveWorker(data).subscribe({
      next: () => {
        this.profileService.saveEquity(this.shareEquityInfo, this.ethnicity, this.hasDisability).subscribe({
          next: () => this.finishSave(continueAfter),
          error: (err) => {
            this.saving.set(false);
            this.errorMessage.set(err.error?.error ?? 'Your profile was saved, but the optional equity info was not.');
          },
        });
      },
      error: (err) => {
        this.saving.set(false);
        this.errorMessage.set(err?.error?.error ?? (err?.status === 0 ? 'Cannot reach the server. Please check your connection and try again.' : 'Could not save your profile.'));
      },
    });
  }

  private finishSave(continueAfter: boolean): void {
    this.saving.set(false);
    this.saved.set(true);
    this.profileExists.set(true);
    if ((this.idNumber ?? '').trim()) {
      this.hasIdNumber.set(true);
      this.idNumber = '';
    }
    this.submitted.set(false);
    if (continueAfter) {
      this.router.navigate(['/my-adverts']);
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  onFileChosen(event: Event, kind: 'cv' | 'photo'): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      this.uploadMessage.set('That file is too big. The limit is 5 MB.');
      return;
    }

    this.uploadMessage.set('Uploading…');
    this.profileService.upload(kind, file).subscribe({
      next: () => {
        this.uploadMessage.set(kind === 'cv' ? 'CV uploaded.' : 'Photo uploaded.');
        if (kind === 'cv') this.hasCv.set(true);
        else this.hasPhoto.set(true);
      },
      error: (err) => this.uploadMessage.set(err.error?.error ?? 'Upload failed.'),
    });
  }
}
