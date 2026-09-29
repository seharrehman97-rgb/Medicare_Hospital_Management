(function() {
  console.log("Mock Backend Active: Simulating Database for GitHub Pages");

  const defaultData = {
    patients: [
      { patient_id: 1, first_name: 'Ahmed', last_name: 'Ali', date_of_birth: '1990-05-14', gender: 'Male', blood_group: 'A+', phone: '0300-1234567', city: 'Jhang' },
      { patient_id: 2, first_name: 'Fatima', last_name: 'Zahra', date_of_birth: '1985-08-22', gender: 'Female', blood_group: 'O+', phone: '0333-9876543', city: 'Lahore' }
    ],
    staff: [
      { staff_id: 1, first_name: 'Sarah', last_name: 'Khan', role: 'Doctor', specialization: 'Cardiologist', department_name: 'Cardiology', status: 'Active', email: 'dr.sarah@medcare.com' },
      { staff_id: 2, first_name: 'Usman', last_name: 'Tariq', role: 'Nurse', specialization: '', department_name: 'ICU', status: 'Active', email: 'usman@medcare.com' }
    ],
    appointments: [
      { appointment_id: 1, patient_name: 'Ahmed Ali', doctor_name: 'Sarah Khan', scheduled_at: '2026-09-28T10:00:00', type: 'Consultation', status: 'Scheduled' }
    ],
    medicines: [
      { medicine_id: 1, name: 'Panadol', generic_name: 'Paracetamol', category: 'Painkiller', dosage_form: 'Tablet', strength: '500mg', quantity_in_stock: 40, reorder_level: 50, expiry_date: '2027-12-01', unit_price: 2.50 }
    ],
    admissions: [
      { admission_id: 1, patient_name: 'Fatima Zahra', ward_name: 'General Ward', diagnosis: 'Dengue Fever', status: 'Admitted' }
    ]
  };

  const originalFetch = window.fetch;
  window.fetch = async function(url, options) {
    if (typeof url === 'string' && url.includes('api/')) {
      let responseBody = {};
      const method = (options && options.method) ? options.method.toUpperCase() : 'GET';
      
      if (url.includes('stats.php')) {
        if (url.includes('patients')) responseBody = { count: defaultData.patients.length };
        else if (url.includes('staff')) responseBody = { count: defaultData.staff.length };
        else if (url.includes('appointments')) responseBody = { count: defaultData.appointments.length };
        else if (url.includes('lowstock')) responseBody = { count: 1 };
      } 
      else if (url.includes('patients.php')) {
        if (method === 'GET') responseBody = defaultData.patients;
        else responseBody = { success: true }; 
      }
      else if (url.includes('staff.php')) {
        if (method === 'GET') responseBody = defaultData.staff;
        else responseBody = { success: true };
      }
      else if (url.includes('appointments.php')) {
        if (method === 'GET') responseBody = defaultData.appointments;
        else responseBody = { success: true };
      }
      else if (url.includes('pharmacy.php')) {
        if (method === 'GET') {
          if (url.includes('type=categories')) responseBody = [{category_id: 1, name: 'Painkiller'}, {category_id: 2, name: 'Antibiotic'}];
          else responseBody = defaultData.medicines; 
        } else {
          responseBody = { success: true };
        }
      }
      else if (url.includes('admissions.php')) {
        if (method === 'GET') responseBody = defaultData.admissions;
      }

      return new Response(JSON.stringify(responseBody), {
        status: 200,
        headers: { 'Content-Type': 'application/json' }
      });
    }
    return originalFetch(url, options);
  };
})();
