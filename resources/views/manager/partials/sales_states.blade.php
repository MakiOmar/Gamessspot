<div class="card card-custom bg-white shadow-sm rounded-lg overflow-hidden">
   <!-- Card Header -->
   <div class="card-header bg-white border-0 py-4">
       <div class="d-flex justify-content-between align-items-center">
           <h3 class="card-title font-weight-bold text-dark mb-0">Sales Stats</h3>
           {{--
           <div class="dropdown">
               <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                   Export
               </button>
               <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                   <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-text me-2"></i>Order</a></li>
                   <li><a class="dropdown-item" href="#"><i class="bi bi-calendar me-2"></i>Event</a></li>
                   <li><a class="dropdown-item" href="#"><i class="bi bi-graph-up me-2"></i>Report</a></li>
                   <li><a class="dropdown-item" href="#"><i class="bi bi-rocket me-2"></i>Post</a></li>
                   <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark me-2"></i>File</a></li>
               </ul>
           </div>
           --}}
       </div>
   </div>

   <!-- Animated Circles Section -->
   <div class="card-body p-4">
       <div class="row g-4">
           <!-- System Assets -->
           <div class="col-md-6">
               <div class="circle-stat">
                   <div class="circle-progress" data-value="75">
                       <div class="circle-inner">
                           <i class="bi bi-bar-chart-fill text-warning"></i>
                       </div>
                   </div>
                   <div class="circle-info mt-3">
                       <h6 class="text-muted mb-1">System Assets</h6>
                       <a href="#" class="text-dark fw-bold">View Report</a>
                   </div>
               </div>
           </div>

           <!-- Users -->
           <div class="col-md-6">
               <div class="circle-stat">
                   <div class="circle-progress" data-value="{{ min(100, ($totalUserCount/100)*100) }}">
                       <div class="circle-inner">
                           <i class="bi bi-people-fill text-primary"></i>
                       </div>
                   </div>
                   <div class="circle-info mt-3">
                       <h6 class="text-muted mb-1">Users</h6>
                       <span class="text-dark fw-bold">{{ $totalUserCount }}</span>
                   </div>
               </div>
           </div>

           <!-- Happy Customers -->
           <div class="col-md-6">
               <div class="circle-stat">
                   <div class="circle-progress" data-value="{{ min(100, ($uniqueBuyerPhoneCount/500)*100) }}">
                       <div class="circle-inner">
                           <i class="bi bi-emoji-smile-fill text-danger"></i>
                       </div>
                   </div>
                   <div class="circle-info mt-3">
                       <h6 class="text-muted mb-1">Happy Customers</h6>
                       <span class="text-dark fw-bold">{{ $uniqueBuyerPhoneCount }}</span>
                   </div>
               </div>
           </div>

           <!-- New Users -->
           <div class="col-md-6">
               <div class="circle-stat">
                   <div class="circle-progress" data-value="{{ min(100, ($newUsersCount/50)*100) }}">
                       <div class="circle-inner">
                           <i class="bi bi-person-plus-fill text-success"></i>
                       </div>
                   </div>
                   <div class="circle-info mt-3">
                       <h6 class="text-muted mb-1">New Users</h6>
                       <span class="text-dark fw-bold">{{ $newUsersCount }}</span>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- Chart Section -->
   <div class="card-footer bg-white border-top-0 pt-0">
       <div class="chart-container" style="height: 200px;">
           <canvas id="salesChart"></canvas>
       </div>
   </div>
</div>