/**
 * JavaScript público del plugin Event Show
 *
 * @package Event_Show
 */

(function ($) {
  "use strict";

  /**
   * Inicializar cuando el DOM esté listo
   */
  $(document).ready(function () {
    // Formulario de registro de asistentes
    initRegistrationForm();

    // Formulario de envío de eventos
    initSubmitEventForm();

    // Carrusel
    initCarousel();

    // Dashboard tabs
    initDashboardTabs();

    // Botones compartir y calendario
    initShareButtons();

    // Datepicker
    initDatepicker();

    // Modales
    initModals();

    // Countdown
    initCountdown();
  });

  /**
   * Inicializar formulario de registro
   */
  function initRegistrationForm() {
    $(".event-registration-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $form.find(".event-register-btn");
      var $messages = $form.find(".form-messages");

      // Desactivar botón
      $btn.prop("disabled", true);
      $btn.find(".btn-text").hide();
      $btn.find(".btn-loading").show();

      // Preparar datos
      var formData = $form.serialize();

      // AJAX
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            $form[0].reset();
          } else {
            $messages
              .removeClass("success")
              .addClass("error")
              .html(response.data.message)
              .show();
          }
        },
        error: function () {
          $messages
            .removeClass("success")
            .addClass("error")
            .html(eventShowData.i18n.error)
            .show();
        },
        complete: function () {
          $btn.prop("disabled", false);
          $btn.find(".btn-text").show();
          $btn.find(".btn-loading").hide();

          // Scroll a mensajes
          $("html, body").animate(
            {
              scrollTop: $messages.offset().top - 100,
            },
            500
          );
        },
      });
    });
  }

  /**
   * Inicializar formulario de envío de eventos
   */
  function initSubmitEventForm() {
    $("#event-submit-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $form.find(".event-submit-btn");
      var $messages = $form.find(".form-messages");

      $btn.prop("disabled", true);
      $btn.find(".btn-text").hide();
      $btn.find(".btn-loading").show();

      var formData = $form.serialize();

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            $form[0].reset();
          } else {
            $messages
              .removeClass("success")
              .addClass("error")
              .html(response.data.message)
              .show();
          }
        },
        error: function () {
          $messages
            .removeClass("success")
            .addClass("error")
            .html(eventShowData.i18n.error)
            .show();
        },
        complete: function () {
          $btn.prop("disabled", false);
          $btn.find(".btn-text").show();
          $btn.find(".btn-loading").hide();

          $("html, body").animate(
            {
              scrollTop: $messages.offset().top - 100,
            },
            500
          );
        },
      });
    });
  }

  /**
   * Inicializar carrusel
   */
  function initCarousel() {
    $(".event-show-carousel").each(function () {
      var $carousel = $(this);
      var $track = $carousel.find(".carousel-track");
      var $slides = $carousel.find(".carousel-slide");
      var currentIndex = 0;

      if ($slides.length <= 1) {
        $carousel.find(".carousel-control, .carousel-indicators").hide();
        return;
      }

      // Navegación
      $carousel.find(".carousel-next").on("click", function () {
        currentIndex = (currentIndex + 1) % $slides.length;
        updateCarousel();
      });

      $carousel.find(".carousel-prev").on("click", function () {
        currentIndex = (currentIndex - 1 + $slides.length) % $slides.length;
        updateCarousel();
      });

      // Indicadores
      $carousel.find(".carousel-indicator").on("click", function () {
        currentIndex = $(this).data("slide");
        updateCarousel();
      });

      // Auto-play
      var autoplay = setInterval(function () {
        currentIndex = (currentIndex + 1) % $slides.length;
        updateCarousel();
      }, 5000);

      $carousel.on("mouseenter", function () {
        clearInterval(autoplay);
      });

      function updateCarousel() {
        $track.css("transform", "translateX(" + -currentIndex * 100 + "%)");
        $carousel
          .find(".carousel-indicator")
          .removeClass("active")
          .eq(currentIndex)
          .addClass("active");
      }
    });
  }

  /**
   * Inicializar tabs del dashboard
   */
  function initDashboardTabs() {
    $(".dashboard-tab").on("click", function () {
      var tabId = $(this).data("tab");

      $(".dashboard-tab").removeClass("active");
      $(this).addClass("active");

      $(".dashboard-tab-content").removeClass("active");
      $("#tab-" + tabId).addClass("active");
    });
  }

  /**
   * Inicializar botones compartir
   */
  function initShareButtons() {
    $(".event-share-btn").on("click", function (e) {
      e.preventDefault();

      var url = window.location.href;
      var title = document.title;

      if (navigator.share) {
        navigator.share({
          title: title,
          url: url,
        });
      } else {
        // Copiar al portapapeles
        var tempInput = $("<input>");
        $("body").append(tempInput);
        tempInput.val(url).select();
        document.execCommand("copy");
        tempInput.remove();
        alert("¡Enlace copiado al portapapeles!");
      }
    });
  }

  /**
   * Inicializar datepicker
   */
  function initDatepicker() {
    if (typeof $.fn.datepicker !== "undefined") {
      $(".event-datepicker").datepicker({
        dateFormat: "dd/mm/yy",
        minDate: 0,
        changeMonth: true,
        changeYear: true,
      });
    }
  }

  /**
   * Inicializar modales
   */
  function initModals() {
    // Abrir modal de organizador
    $(document).on("click", ".create-organizer-link", function (e) {
      e.preventDefault();
      $("#create-organizer-modal").fadeIn();
    });

    // Abrir modal de lugar
    $(document).on("click", ".create-location-link", function (e) {
      e.preventDefault();
      $("#create-location-modal").fadeIn();
    });

    // Cerrar modal
    $(".modal-close").on("click", function () {
      $(this).closest(".event-modal").fadeOut();
    });

    $(window).on("click", function (e) {
      if ($(e.target).hasClass("event-modal")) {
        $(".event-modal").fadeOut();
      }
    });

    // Formulario crear organizador
    $("#create-organizer-form").on("submit", function (e) {
      e.preventDefault();

      var formData =
        $(this).serialize() +
        "&action=event_show_create_organizer&nonce=" +
        eventShowData.nonce;

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            // Añadir al select
            $("#event_organizer")
              .append(
                $("<option>", {
                  value: response.data.term_id,
                  text: response.data.term_name,
                })
              )
              .val(response.data.term_id);

            $("#create-organizer-modal").fadeOut();
            $("#create-organizer-form")[0].reset();
            alert(response.data.message);
          } else {
            alert(response.data.message);
          }
        },
      });
    });

    // Formulario crear lugar
    $("#create-location-form").on("submit", function (e) {
      e.preventDefault();

      var formData =
        $(this).serialize() +
        "&action=event_show_create_location&nonce=" +
        eventShowData.nonce;

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            $("#event_location")
              .append(
                $("<option>", {
                  value: response.data.term_id,
                  text: response.data.term_name,
                })
              )
              .val(response.data.term_id);

            $("#create-location-modal").fadeOut();
            $("#create-location-form")[0].reset();
            alert(response.data.message);
          } else {
            alert(response.data.message);
          }
        },
      });
    });
  }

  /**
   * Inicializar countdown
   */
  function initCountdown() {
    $(".slide-countdown").each(function () {
      var $countdown = $(this);
      var totalSeconds = parseInt($countdown.data("countdown"));

      if (!totalSeconds || totalSeconds <= 0) {
        return;
      }

      setInterval(function () {
        totalSeconds--;

        if (totalSeconds <= 0) {
          return;
        }

        var days = Math.floor(totalSeconds / (60 * 60 * 24));
        var hours = Math.floor((totalSeconds % (60 * 60 * 24)) / (60 * 60));
        var minutes = Math.floor((totalSeconds % (60 * 60)) / 60);

        $countdown
          .find(".countdown-item")
          .eq(0)
          .find(".countdown-value")
          .text(days);
        $countdown
          .find(".countdown-item")
          .eq(1)
          .find(".countdown-value")
          .text(hours);
        $countdown
          .find(".countdown-item")
          .eq(2)
          .find(".countdown-value")
          .text(minutes);
      }, 1000);
    });
  }

  /**
   * Exportar asistentes
   */
  $(document).on("click", ".event-export-attendees", function (e) {
    e.preventDefault();

    var eventId = $(this).data("event-id");
    var url =
      eventShowData.ajaxUrl +
      "?action=event_show_export_attendees&event_id=" +
      eventId +
      "&nonce=" +
      eventShowData.nonce;

    window.location.href = url;
  });
})(jQuery);
