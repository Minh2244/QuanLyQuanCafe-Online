const menuOpenButton = document.querySelector("#menu-open-button");
const menuCloseOpenButton = document.querySelector("#menu-close-button");
// toggle mobile menu visibility
menuOpenButton.addEventListener("click", () => {
  document.body.classList.toggle("show-mobile-menu");
});

// close menu when close button is clicked
menuCloseOpenButton.addEventListener("click", () => menuOpenButton.click());

// Kiểm tra đơn hàng mới
function checkNewOrders() {
  fetch("../../../model/count_new_orders.php")
    .then((response) => response.json())
    .then((data) => {
      const orderCountElements = document.querySelectorAll(".order-count");
      orderCountElements.forEach((element) => {
        element.textContent = data.unassigned_orders;

        // Hiển thị hoặc ẩn số đơn hàng mới
        if (data.unassigned_orders > 0) {
          element.style.display = "flex";
        } else {
          element.style.display = "none";
        }
      });

      // Cập nhật các thẻ hiển thị số đơn hàng
      const newOrdersCount = document.querySelectorAll(".new-orders-count");
      newOrdersCount.forEach((element) => {
        element.textContent = data.unassigned_orders;
      });
    })
    .catch((error) => console.error("Lỗi khi kiểm tra đơn hàng mới:", error));
}

// Kiểm tra đơn hàng mới mỗi 30 giây
setInterval(checkNewOrders, 30000);

// Kiểm tra ngay khi trang được tải
document.addEventListener("DOMContentLoaded", checkNewOrders);
