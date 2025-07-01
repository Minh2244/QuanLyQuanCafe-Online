document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".form-modern");
  if (!form) return;

  // Validate họ tên
  function validateName(name) {
    // Kiểm tra độ dài tối thiểu và không chứa số hoặc ký tự đặc biệt
    const nameRegex = /^[A-Za-zÀ-ỹ\s]{2,50}$/u;
    return nameRegex.test(name);
  }

  // Validate số điện thoại
  function validatePhone(phone) {
    // Số điện thoại Việt Nam bắt đầu bằng 0, có 10 số
    const phoneRegex = /^(0[0-9]{9})$/;
    return phoneRegex.test(phone);
  }

  // Validate địa chỉ
  function validateAddress(address) {
    // Địa chỉ không được quá ngắn và không chứa ký tự đặc biệt
    return address.trim().length >= 5 && !/[<>{}[\]\\]/.test(address);
  }

  form.addEventListener("submit", function (e) {
    // Không cần ngăn chặn submit form mặc định nữa
    // Form không còn chứa các trường để validate
    // Mọi thứ đều đã được xác thực từ phía server
  });
});
