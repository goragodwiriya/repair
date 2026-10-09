/**
 * modules/repair/admin.js
 *
 * ลงทะเบียน route และ formatter ของโมดูลรับซ่อม
 */
EventManager.on('router:initialized', () => {
  RouterManager.register('/repair-jobs', {
    template: 'repair/jobs.html',
    title: '{LNG_Repair list}',
    requireAuth: true
  });

  RouterManager.register('/repair-receive', {
    template: 'repair/receive.html',
    title: '{LNG_Get a repair}',
    menuPath: '/repair-jobs',
    requireAuth: true
  });

  RouterManager.register('/repair-detail', {
    template: 'repair/detail.html',
    title: '{LNG_Repair job description}',
    menuPath: '/repair-jobs',
    requireAuth: true
  });

  RouterManager.register('/repair-statuses', {
    template: 'repair/statuses.html',
    title: '{LNG_Repair status}',
    requireAuth: true
  });

  RouterManager.register('/repair-settings', {
    template: 'repair/settings.html',
    title: '{LNG_Repair Settings}',
    requireAuth: true
  });

  // หน้าติดตามสถานะสำหรับลูกค้า เปิดจาก QR Code บนใบรับซ่อม ไม่ต้องเข้าระบบ
  RouterManager.register('/repair-track', {
    template: 'repair/track.html',
    title: '{LNG_Repair status}',
    requireAuth: false,
    requireGuest: false
  });

  // หน้าแรกของระบบคือสรุปงานซ่อม
  RouterManager.register('/', {
    template: 'repair/dashboard.html',
    title: '{LNG_Repair jobs}',
    requireAuth: true
  });
});

/**
 * ฟอร์มรับซ่อม — ปุ่ม "บันทึก" กับ "บันทึก & พิมพ์ใบรับซ่อม" ใช้ฟอร์มเดียวกัน
 * ต่างกันแค่ค่าของ input#print ที่ส่งไปกับฟอร์ม
 *
 * @param {HTMLElement} element ฟอร์ม
 *
 * @returns {Function} ฟังก์ชันสำหรับถอด event ตอนออกจากหน้า
 */
function initRepairReceive(element) {
  const form = element.tagName === 'FORM' ? element : element.querySelector('form');
  if (!form) return;

  const printField = form.querySelector('#print');
  const buttons = form.querySelectorAll('[data-repair-print]');

  const onClick = (e) => {
    if (printField) {
      printField.value = e.currentTarget.getAttribute('data-repair-print');
    }
  };

  buttons.forEach((button) => button.addEventListener('click', onClick));

  return () => {
    buttons.forEach((button) => button.removeEventListener('click', onClick));
  };
}

/**
 * คอลัมน์เลขที่ใบรับซ่อม พร้อมปุ่มเปิดใบรับซ่อมในแท็บใหม่
 *
 * @param {HTMLElement} cell
 * @param {string} rawValue
 * @param {Object} rowData
 */
function formatRepairJobId(cell, rawValue, rowData) {
  const link = document.createElement('a');
  link.className = 'repair-job-id icon-print';
  link.href = rowData.print_url;
  link.target = '_blank';
  link.rel = 'noopener';
  link.title = I18nManager.translate('Print receipt');
  link.textContent = rawValue || '';
  cell.innerHTML = '';
  cell.appendChild(link);
}
