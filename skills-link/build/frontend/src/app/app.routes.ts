import { Routes } from '@angular/router';
import { roleGuard } from './guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () => import('./pages/home/home.component')
      .then(m => m.HomeComponent)
  },

  {
    path: 'login',
    loadComponent: () => import('./pages/login/login.component')
      .then(m => m.LoginComponent)
  },

  {
    path: 'signup',
    loadComponent: () => import('./pages/signup/signup.component')
      .then(m => m.SignupComponent)
  },

  {
    path: 'forgot-password',
    loadComponent: () => import('./pages/forgot-password/forgot-password.component')
      .then(m => m.ForgotPasswordComponent)
  },

  {
    path: 'reset-password',
    loadComponent: () => import('./pages/reset-password/reset-password.component')
      .then(m => m.ResetPasswordComponent)
  },

  {
    path: 'dashboard',
    canActivate: [roleGuard('jobseeker')],
    loadComponent: () => import('./pages/jobseeker-dashboard/jobseeker-dashboard.component')
      .then(m => m.JobseekerDashboardComponent)
  },

  {
    path: 'recruiter-dashboard',
    canActivate: [roleGuard('recruiter')],
    loadComponent: () => import('./pages/recruiter-dashboard/recruiter-dashboard.component')
      .then(m => m.RecruiterDashboardComponent)
  },

  {
    path: 'admin-home',
    canActivate: [roleGuard('admin')],
    loadComponent: () => import('./pages/admin-home/admin-home.component')
      .then(m => m.AdminHomeComponent)
  },

  {
    path: 'worker-profile',
    canActivate: [roleGuard('jobseeker')],
    loadComponent: () => import('./pages/worker-profile/worker-profile.component')
      .then(m => m.WorkerProfileComponent)
  },

  {
    path: 'recruiter-profile',
    canActivate: [roleGuard('recruiter')],
    loadComponent: () => import('./pages/recruiter-profile/recruiter-profile.component')
      .then(m => m.RecruiterProfileComponent)
  },
  {
    path: 'admin-dashboard',
    canActivate: [roleGuard('admin')],
    loadComponent: () => import('./pages/admin-dashboard/admin-dashboard.component')
      .then(m => m.AdminDashboardComponent)
  },

  {
    path: 'reports',
    canActivate: [roleGuard('admin')],
    loadComponent: () => import('./pages/reports/reports.component')
      .then(m => m.ReportsComponent)
  },

  

  {
    path: 'jobs',
    loadComponent: () => import('./pages/job-list/job-list.component')
      .then(m => m.JobListComponent)
  },

  {
    path: 'jobs/:id',
    loadComponent: () => import('./pages/job-details/job-details.component')
      .then(m => m.JobDetailsComponent)
  },

  {
    path: 'jobs/:id/applicants',
    canActivate: [roleGuard('recruiter')],
    loadComponent: () => import('./pages/applicants/applicants.component')
      .then(m => m.ApplicantsComponent)
  },

  {
    path: 'post-job',
    canActivate: [roleGuard('recruiter')],
    loadComponent: () => import('./pages/job-post/job-post.component')
      .then(m => m.JobPostComponent)
  },

  {
    path: 'my-jobs',
    canActivate: [roleGuard('recruiter')],
    loadComponent: () => import('./pages/my-jobs/my-jobs.component')
      .then(m => m.MyJobsComponent)
  },

  {
    path: 'find-workers',
    canActivate: [roleGuard('recruiter')],
    loadComponent: () => import('./pages/find-workers/find-workers.component')
      .then(m => m.FindWorkersComponent)
  },

  {
    path: 'my-adverts',
    canActivate: [roleGuard('jobseeker')],
    loadComponent: () => import('./pages/my-adverts/my-adverts.component')
      .then(m => m.MyAdvertsComponent)
  },

  {
    path: 'admin',
    canActivate: [roleGuard('admin')],
    loadComponent: () => import('./pages/admin/admin.component')
      .then(m => m.AdminComponent)
  },

  {
    path: '**',
    redirectTo: ''
  }
];
