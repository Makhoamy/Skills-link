import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ProfileService } from '../../services/profile.service';
import { JobService } from '../../services/job.service';
import { Province } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-recruiter-profile',
  standalone: true,
  imports: [FormsModule, PageNavComponent],
  templateUrl: './recruiter-profile.component.html',
  styleUrl: './recruiter-profile.component.css',
})
export class RecruiterProfileComponent implements OnInit {
  companyName = '';
  registrationNumber = '';
  address = '';
  description = '';
  zipCode = '';
  city = '';
  provinceId: number | null = null;
  website = '';
  contactFirstName = '';
  contactLastName = '';
  contactPosition = '';

  provinces = signal<Province[]>([]);
  verificationStatus = signal<string>('unverified');
  hasRegDoc = signal(false);
  profileExists = signal(false);

  regDocFile: File | null = null;

  saved = signal(false);
  saving = signal(false);
  errorMessage = signal<string | null>(null);

  submitted = signal(false);

  constructor(private profileService: ProfileService, private jobService: JobService, private router: Router) {}

  ngOnInit(): void {
    this.jobService.getProvinces().subscribe((list) => this.provinces.set(list));

    this.profileService.getRecruiter().subscribe({
      next: (res) => {
        const p = res.profile;
        if (!p) return;
        this.profileExists.set(true);
        this.companyName = p.company_name ?? '';
        this.registrationNumber = p.registration_number ?? '';
        this.address = p.address ?? '';
        this.description = p.company_description ?? '';
        this.zipCode = p.zip_code ?? '';
        this.city = p.city ?? '';
        this.provinceId = p.province_id !== null && p.province_id !== undefined ? Number(p.province_id) : null;
        this.website = p.website ?? '';
        this.contactFirstName = p.contact_first_name ?? '';
        this.contactLastName = p.contact_last_name ?? '';
        this.contactPosition = p.contact_position ?? '';
        this.verificationStatus.set(p.verification_status);
        this.hasRegDoc.set(p.has_registration_doc);
      },
      error: () => this.errorMessage.set('Could not load your company profile.'),
    });
  }

  onFileChosen(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.regDocFile = input.files?.[0] ?? null;
  }

  /** Returns a map of field -> message. Empty map means the form is valid. */
  validate(): Record<string, string> {
    const e: Record<string, string> = {};
    const nameOk = /^[\p{L}][\p{L}\s'’.\-]*$/u;

    const company = (this.companyName ?? '').trim();
    if (!company) e['companyName'] = 'Company name is required.';
    else if (company.length < 2) e['companyName'] = 'Company name must be at least 2 characters.';
    else if (company.length > 150) e['companyName'] = 'Company name is too long (max 150).';

    if ((this.registrationNumber ?? '').trim().length > 50) e['registrationNumber'] = 'Registration number is too long (max 50).';

    if ((this.address ?? '').trim().length > 200) e['address'] = 'Address is too long (max 200).';

    const city = (this.city ?? '').trim();
    if (!city) e['city'] = 'City is required.';
    else if (city.length > 80) e['city'] = 'City is too long (max 80).';

    if (this.provinceId === null || this.provinceId === undefined) e['province'] = 'Please select your province.';

    const zip = (this.zipCode ?? '').trim();
    if (zip && !/^[A-Za-z0-9 \-]{3,10}$/.test(zip)) e['zipCode'] = 'Enter a valid postal code (e.g. 0002).';

    if ((this.description ?? '').trim().length > 1000) e['description'] = 'Description is too long (max 1000 characters).';

    const site = (this.website ?? '').trim();
    if (site) {
      try {
        const u = new URL(/^https?:\/\//i.test(site) ? site : 'https://' + site);
        if (!u.hostname.includes('.')) throw new Error('bad host');
      } catch {
        e['website'] = 'Enter a valid website address (e.g. www.example.co.za).';
      }
    }

    const cf = (this.contactFirstName ?? '').trim();
    if (!cf) e['contactFirstName'] = 'Contact first name is required.';
    else if (!nameOk.test(cf)) e['contactFirstName'] = 'First name contains invalid characters.';

    const cl = (this.contactLastName ?? '').trim();
    if (!cl) e['contactLastName'] = 'Contact last name is required.';
    else if (!nameOk.test(cl)) e['contactLastName'] = 'Last name contains invalid characters.';

    if ((this.contactPosition ?? '').trim().length > 80) e['contactPosition'] = 'Position is too long (max 80).';

    if (this.regDocFile) {
      if (this.regDocFile.size > 5 * 1024 * 1024) e['regDoc'] = 'The registration document is too big. The limit is 5 MB.';
      else if (!/\.(pdf|jpe?g|png)$/i.test(this.regDocFile.name)) e['regDoc'] = 'Please choose a PDF, JPG or PNG file.';
    }
    return e;
  }

  get errors(): Record<string, string> {
    return this.submitted() ? this.validate() : {};
  }

  /** Save the company profile. When continueAfter is true, go on to Post a Job once everything has saved. */
  save(continueAfter = false): void {
    this.errorMessage.set(null);
    this.saved.set(false);
    this.submitted.set(true);
    if (this.saving()) return;

    const problems = this.validate();
    const keys = Object.keys(problems);
    if (keys.length) {
      this.errorMessage.set('Please fix the highlighted fields before saving.');
      setTimeout(() => document.getElementById(keys[0])?.focus(), 0);
      return;
    }

    this.saving.set(true);
    this.profileService
      .saveRecruiter({
        company_name: this.companyName.trim(),
        registration_number: (this.registrationNumber ?? '').trim(),
        address: (this.address ?? '').trim(),
        company_description: (this.description ?? '').trim(),
        zip_code: (this.zipCode ?? '').trim(),
        city: this.city.trim(),
        province_id: this.provinceId,
        website: (this.website ?? '').trim(),
        contact_first_name: this.contactFirstName.trim(),
        contact_last_name: this.contactLastName.trim(),
        contact_position: (this.contactPosition ?? '').trim(),
      })
      .subscribe({
        next: () => {
          this.profileExists.set(true);
          if (this.regDocFile) {
            this.uploadDoc(this.regDocFile, continueAfter);
          } else {
            this.finishSave(continueAfter);
          }
        },
        error: (err) => {
          this.saving.set(false);
          this.errorMessage.set(
            err?.error?.error ??
              (err?.status === 0 ? 'Cannot reach the server. Please check your connection and try again.' : 'Could not save your company profile.')
          );
        },
      });
  }

  private uploadDoc(file: File, continueAfter: boolean): void {
    this.profileService.upload('registration_doc', file).subscribe({
      next: () => {
        this.hasRegDoc.set(true);
        this.verificationStatus.set('pending');
        this.regDocFile = null;
        const input = document.getElementById('regDoc') as HTMLInputElement | null;
        if (input) input.value = '';
        this.finishSave(continueAfter);
      },
      error: (err) => {
        this.saving.set(false);
        this.errorMessage.set(err?.error?.error ?? 'Your details were saved, but the document upload failed. Please try the upload again.');
      },
    });
  }

  private finishSave(continueAfter: boolean): void {
    this.saving.set(false);
    this.saved.set(true);
    this.submitted.set(false);
    // Reload so the verification status shown matches what the server stored.
    this.profileService.getRecruiter().subscribe({
      next: (res) => {
        if (res.profile) {
          this.verificationStatus.set(res.profile.verification_status);
          this.hasRegDoc.set(res.profile.has_registration_doc);
        }
      },
      error: () => {},
    });
    if (continueAfter) {
      this.router.navigate(['/post-job']);
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }
}
