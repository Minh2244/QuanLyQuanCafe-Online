// JavaScript cho modal chọn phương thức thanh toán
document.addEventListener("DOMContentLoaded", function () {
  // Lấy tham chiếu đến các phần tử
  const paymentModal = document.getElementById("paymentModal");
  const billQRModal = document.getElementById("billQRModal");
  const closeButtons = document.getElementsByClassName("close-modal");
  const cancelBtn = document.querySelector(".cancel-btn");
  const confirmBtn = document.querySelector(".confirm-btn");
  const paymentForm = document.getElementById("paymentForm");
  const paymentMethodInputs = document.querySelectorAll(
    'input[name="payment_method"]'
  );
  const qrCodeSection = document.getElementById("qrCodeSection");
  const qrLoading = document.querySelector(".qr-loading");
  const qrContent = document.querySelector(".qr-content");
  const qrCodeImage = document.getElementById("qrCodeImage");
  const amountDisplay = document.getElementById("amount");
  const transferDescDisplay = document.getElementById("transferDescription");
  const bankNameDisplay = document.getElementById("bankName");
  const accountNoDisplay = document.getElementById("accountNo");
  const accountNameDisplay = document.getElementById("accountName");
  const branchDisplay = document.getElementById("branch");

  // Elements for bill
  const billId = document.getElementById("billId");
  const billDate = document.getElementById("billDate");
  const billStaff = document.getElementById("billStaff");
  const billItems = document.getElementById("billItems");
  const billTotal = document.getElementById("billTotal");

  // Hiển thị modal thanh toán
  document
    .getElementById("showPaymentModal")
    ?.addEventListener("click", function () {
      paymentModal.style.display = "block";
    });

  // Đóng modal khi click vào nút đóng
  Array.from(closeButtons).forEach((button) => {
    button.addEventListener("click", function () {
      const modal = button.closest(".payment-modal");
      modal.style.display = "none";

      // Nếu đang đóng modal hóa đơn (billQRModal), reload trang
      if (modal.id === "billQRModal") {
        window.location.href = "index.php?act=menu";
      }
    });
  });

  // Đóng modal khi click ra ngoài
  window.addEventListener("click", function (event) {
    if (event.target === paymentModal) {
      paymentModal.style.display = "none";
    }
    if (event.target === billQRModal) {
      billQRModal.style.display = "none";
      // Reload trang khi đóng modal hóa đơn
      window.location.href = "index.php?act=menu";
    }
  });

  // Đóng modal khi click nút hủy
  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () {
      paymentModal.style.display = "none";
    });
  }

  // Xử lý khi thay đổi phương thức thanh toán
  paymentMethodInputs.forEach((input) => {
    input.addEventListener("change", function () {
      if (this.value === "bank") {
        confirmBtn.textContent = "Tạo mã QR";
      } else {
        confirmBtn.textContent = "Xác nhận";
      }
    });
  });

  // Xử lý khi xác nhận thanh toán
  if (confirmBtn && paymentForm) {
    confirmBtn.addEventListener("click", function () {
      const paymentMethod = document.querySelector(
        'input[name="payment_method"]:checked'
      ).value;
      const billInfo = updateBillInfo();

      if (!billInfo) return;

      if (paymentMethod === "bank") {
        // Hiển thị modal hóa đơn và QR
        paymentModal.style.display = "none";
        billQRModal.style.display = "block";

        // Tạo mã QR và lưu đơn hàng
        showQRCode(billInfo.totalAmount, `TT${billInfo.billCode}`);
        saveOrder(
          billInfo.billCode,
          billInfo.cartData,
          billInfo.totalAmount,
          "bank"
        );
      } else {
        // Xử lý thanh toán tiền mặt
        saveOrder(
          billInfo.billCode,
          billInfo.cartData,
          billInfo.totalAmount,
          "cod"
        );
      }
    });
  }

  // Hàm cập nhật thông tin hóa đơn
  function updateBillInfo() {
    const now = new Date();
    const billCode = generateOrderCode();

    // Lấy dữ liệu từ form
    const cartDataInput = document.querySelector('input[name="cart_data"]');
    const totalAmountInput = document.querySelector(
      'input[name="total_amount"]'
    );
    const staffNameInput = document.querySelector('input[name="staff_name"]');

    try {
      // Parse dữ liệu giỏ hàng
      const cartData = JSON.parse(cartDataInput?.value || "[]");
      const totalAmount = parseFloat(totalAmountInput?.value || "0");
      const staffName = staffNameInput?.value || "Nhân viên";

      // Kiểm tra dữ liệu
      if (!Array.isArray(cartData) || cartData.length === 0) {
        throw new Error("Giỏ hàng trống");
      }

      // Cập nhật thông tin cơ bản
      document.getElementById("billId").textContent = billCode;
      document.getElementById("billDate").textContent = formatDateTime(now);
      document.getElementById("billStaff").textContent = staffName;

      // Cập nhật danh sách sản phẩm
      let itemsHtml = "";
      cartData.forEach((item, index) => {
        const price = parseFloat(item.price || 0);
        const quantity = parseInt(item.quantity || 0);
        const total = price * quantity;

        itemsHtml += `
          <tr>
            <td>${index + 1}</td>
            <td>${item.name || ""}</td>
            <td style="text-align: right">${formatCurrency(price)} VNĐ</td>
            <td style="text-align: center">${quantity}</td>
            <td style="text-align: right">${formatCurrency(total)} VNĐ</td>
          </tr>
        `;
      });
      document.getElementById("billItems").innerHTML = itemsHtml;

      // Cập nhật tổng tiền
      $("#billTotal").text(formatCurrency(totalAmount));

      return {
        billCode,
        cartData,
        totalAmount,
        staffName,
      };
    } catch (error) {
      console.error("Error updating bill info:", error);
      alert("Có lỗi khi cập nhật thông tin hóa đơn: " + error.message);
      return null;
    }
  }

  // Hàm tạo mã đơn hàng
  function generateOrderCode() {
    const date = new Date();
    const randomNum = Math.floor(Math.random() * 1000)
      .toString()
      .padStart(3, "0");
    return (
      "#" +
      date.getFullYear().toString().substr(-2) +
      padNumber(date.getMonth() + 1) +
      padNumber(date.getDate()) +
      padNumber(date.getHours()) +
      padNumber(date.getMinutes()) +
      randomNum
    );
  }

  // Hàm thêm số 0 phía trước nếu số < 10
  function padNumber(num) {
    return num.toString().padStart(2, "0");
  }

  // Hàm format ngày giờ
  function formatDateTime(date) {
    return `${padNumber(
      date.getDate()
    )}/${padNumber(date.getMonth() + 1)}/${date.getFullYear()} ${padNumber(date.getHours())}:${padNumber(date.getMinutes())}:${padNumber(date.getSeconds())}`;
  }

  // Hàm format tiền tệ
  function formatCurrency(amount) {
    return new Intl.NumberFormat("vi-VN").format(Math.round(amount));
  }

  // Hàm hiển thị mã QR
  function showQRCode(amount, description) {
    qrCodeSection.style.display = "block";
    qrLoading.style.display = "flex";
    qrContent.style.display = "none";

    // Format số tiền
    amountDisplay.textContent = formatCurrency(amount) + " VNĐ";
    transferDescDisplay.textContent = description;

    // Gọi API để tạo mã QR
    fetch("process/generate_qr.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `amount=${amount}&description=${description}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          qrCodeImage.src = data.qr_url;
          qrCodeImage.onload = function () {
            qrLoading.style.display = "none";
            qrContent.style.display = "flex";
          };

          // Cập nhật thông tin ngân hàng
          const bankInfo = data.bank_info;
          bankNameDisplay.textContent = bankInfo.bank_name;
          accountNoDisplay.textContent = bankInfo.account_no;
          accountNameDisplay.textContent = bankInfo.account_name;
          branchDisplay.textContent = bankInfo.branch;
          amountDisplay.textContent = formatCurrency(bankInfo.amount);
          transferDescDisplay.textContent = bankInfo.description;
        } else {
          alert("Không thể tạo mã QR: " + data.message);
          qrCodeSection.style.display = "none";
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Có lỗi xảy ra khi tạo mã QR");
        qrCodeSection.style.display = "none";
      });
  }

  // Hàm lưu đơn hàng vào database
  function saveOrder(billCode, cartData, totalAmount, paymentMethod) {
    const formData = new FormData();
    formData.append("bill_code", billCode);
    formData.append("cart_data", JSON.stringify(cartData));
    formData.append("total_amount", totalAmount);
    formData.append("payment_method", paymentMethod);

    fetch("process/process_payment.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          if (paymentMethod === "cod") {
            // Ẩn modal thanh toán
            paymentModal.style.display = "none";
            // Hiển thị modal hóa đơn
            billQRModal.style.display = "block";
            // Ẩn phần QR code vì là thanh toán tiền mặt
            qrCodeSection.style.display = "none";
            // Cập nhật badge thanh toán
            document.querySelector(".payment-badge").textContent = "Tiền mặt";
            document
              .querySelector(".payment-badge")
              .classList.remove("payment-bank");
            document
              .querySelector(".payment-badge")
              .classList.add("payment-cash");
          }
        } else {
          throw new Error(data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Có lỗi xảy ra khi lưu đơn hàng: " + error.message);
      });
  }
});
