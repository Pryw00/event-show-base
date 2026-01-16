/**
 * JavaScript público del plugin Event Show
 *
 * @package Event_Show
 */

(function ($) {
  "use strict";

  // Previsualización de imagen banner
  $(document).on("change", "#event_banner", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#event_banner_preview").attr("src", e.target.result).show();
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#event_banner_preview").hide();
    }
  });

  // Previsualización de miniatura
  $(document).on("change", "#event_thumbnail", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#event_thumbnail_preview").attr("src", e.target.result).show();
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#event_thumbnail_preview").hide();
    }
  });

  // Previsualización de banner en modal de edición
  $(document).on("change", "#edit_event_banner", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#edit_banner_preview").html(
          '<img src="' +
            e.target.result +
            '" style="max-width:100%; height:auto; border-radius:8px;">'
        );
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#edit_banner_preview").html("");
    }
  });

  // Previsualización de imagen grid en modal de edición
  $(document).on("change", "#edit_event_grid_image", function (e) {
    var input = this;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#edit_grid_image_preview").html(
          '<img src="' +
            e.target.result +
            '" style="max-width:100%; height:auto; border-radius:8px;">'
        );
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $("#edit_grid_image_preview").html("");
    }
  });

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

    // Slider
    initSlider();

    // Dashboard tabs
    initDashboardTabs();

    // Botones compartir y calendario
    initShareButtons();

    // Datepicker
    initDatepicker();

    // Modales
    initModals();

    // Modal editar evento
    $(document).on("click", ".edit-event-link", function (e) {
      e.preventDefault();
      var eventId = $(this).data("event-id");

      // Obtener datos del evento via AJAX
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: {
          action: "event_show_get_event_data",
          event_id: eventId,
          nonce: eventShowData.nonce,
        },
        success: function (response) {
          if (response.success) {
            $("#edit_event_id").val(eventId);
            $("#edit_event_description").val(response.data.description);
            $("#edit_event_date").val(response.data.event_date);
            $("#edit_event_time").val(response.data.event_time);
            $("#edit_event_end_date").val(response.data.event_end_date || "");
            $("#edit_event_end_time").val(response.data.event_end_time || "");
            $("#edit_event_category").val(response.data.category || "");
            $("#edit_event_age").val(response.data.age_classification || "");
            $("#edit_event_location").val(response.data.location || "");
            $("#edit_event_capacity").val(response.data.max_attendees || "");

            // Mostrar previsualizaciones de imágenes existentes
            if (response.data.banner_url) {
              $("#edit_banner_preview").html(
                '<img src="' +
                  response.data.banner_url +
                  '" style="max-width:100%; height:auto; border-radius:8px;">'
              );
            } else {
              $("#edit_banner_preview").html("");
            }

            if (response.data.grid_image_url) {
              $("#edit_grid_image_preview").html(
                '<img src="' +
                  response.data.grid_image_url +
                  '" style="max-width:100%; height:auto; border-radius:8px;">'
              );
            } else {
              $("#edit_grid_image_preview").html("");
            }

            $("#edit-event-modal").fadeIn();
          } else {
            alert(response.data.message || "Error al cargar evento");
          }
        },
      });
    });

    // Modal editar organizador
    $(document).on("click", ".edit-organizer-link", function (e) {
      e.preventDefault();
      var $btn = $(this);
      $("#edit_org_id").val($btn.data("org-id"));
      $("#edit_org_name").val($btn.data("org-name"));
      $("#edit_org_email").val($btn.data("org-email"));
      $("#edit_org_phone").val($btn.data("org-phone"));
      $("#edit_org_website").val($btn.data("org-website"));
      // Imagen/logo
      var imgId = $btn.data("org-image") || "";
      var imgUrl = "";
      if (imgId) {
        imgUrl = $btn.closest(".dashboard-card").find("img").attr("src") || "";
      }
      $("#edit_org_image").val(imgId);
      if (imgUrl) {
        $("#edit_org_image_preview").attr("src", imgUrl).show();
        $("#edit_org_image_remove").show();
      } else {
        $("#edit_org_image_preview").hide();
        $("#edit_org_image_remove").hide();
      }
      $("#edit-organizer-modal").fadeIn();
    });

    // Vista previa de imagen seleccionada
    $(document).on("change", "#edit_org_image_file", function (e) {
      var input = this;
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#edit_org_image_preview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
      } else {
        $("#edit_org_image_preview").hide();
      }
    });

    // Countdown
    initCountdown();
  });

  /**
   * Inicializar formulario de registro
   */
  function initRegistrationForm() {
    $("#event-submit-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $form.find(".event-submit-btn");
      var $messages = $form.find(".form-messages");

      $btn.prop("disabled", true);
      $btn.find(".btn-text").hide();
      $btn.find(".btn-loading").show();

      var formData = new FormData($form[0]);

      // Adjuntar archivos manualmente si es necesario
      var banner = $("#event_banner")[0].files[0];
      var thumb = $("#event_thumbnail")[0].files[0];
      if (banner) formData.append("event_banner", banner);
      if (thumb) formData.append("event_thumbnail", thumb);

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            $form[0].reset();
            $("#event_banner_preview, #event_thumbnail_preview").hide();
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
      }, 3000);

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
    // Abrir modal de crear evento
    $(document).on("click", "#open-create-event-modal", function (e) {
      e.preventDefault();
      $("#create-event-modal").fadeIn();
    });
    // Abrir modal de organizador
    $(document).on("click", ".create-organizer-link", function (e) {
      e.preventDefault();
      $("#create-organizer-modal").fadeIn();
    });

    // Abrir modal de lugar (flotante)
    $(document).on("click", ".create-location-link", function (e) {
      e.preventDefault();
      $("#create-location-modal").fadeIn();
    });

    // Cerrar modal (soporta .event-modal y .event-modal-overlay)
    $(document).on("click", ".modal-close", function () {
      $(this).closest(".event-modal, .event-modal-overlay").fadeOut();
    });

    $(window).on("click", function (e) {
      if (
        $(e.target).hasClass("event-modal") ||
        $(e.target).hasClass("event-modal-overlay")
      ) {
        $(".event-modal, .event-modal-overlay").fadeOut();
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

    // Vista previa de imagen lugar
    $(document).on("change", "#create_loc_image_file", function (e) {
      var input = this;
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#create_loc_image_preview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
      } else {
        $("#create_loc_image_preview").hide();
      }
    });

    // Formulario crear lugar (con imagen)
    $(document).on("submit", "#create-location-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var formData = new FormData($form[0]);
      formData.append("action", "event_show_create_location");
      formData.append("nonce", eventShowData.nonce);
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $("#create-location-modal").fadeOut();
            $("#create-location-form")[0].reset();
            $("#create_loc_image_preview").hide();
            alert(response.data.message);
            location.reload();
          } else {
            alert(response.data.message);
          }
        },
      });
    });

    // Guardar edición de organizador
    $(document).on("submit", "#edit-organizer-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var formData = new FormData($form[0]);
      formData.append("action", "event_show_edit_organizer");
      formData.append("nonce", eventShowData.nonce);
      var $btn = $form.find("button[type='submit']");
      $btn.prop("disabled", true);
      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            alert(response.data.message);
            $("#edit-organizer-modal").fadeOut();
            location.reload();
          } else {
            alert(response.data.message);
          }
        },
        complete: function () {
          $btn.prop("disabled", false);
        },
      });
    });

    // Guardar edición de evento
    $(document).on("submit", "#edit-event-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find("button[type='submit']");
      var $messages = $form.find(".form-messages");

      $btn.prop("disabled", true).text("Guardando...");

      var formData = new FormData(this);
      formData.append("action", "event_show_edit_event");
      formData.append("nonce", eventShowData.nonce);

      $.ajax({
        url: eventShowData.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $messages
              .removeClass("error")
              .addClass("success")
              .html(response.data.message)
              .show();
            setTimeout(function () {
              $("#edit-event-modal").fadeOut();
              location.reload();
            }, 1500);
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
            .html("Error al guardar")
            .show();
        },
        complete: function () {
          $btn.prop("disabled", false).text("Guardar Cambios");
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
      if (!totalSeconds || totalSeconds <= 0) return;

      // Selección de elementos
      var $days = $countdown.find(".clock-day");
      var $hours = $countdown.find(".clock-hours");
      var $minutes = $countdown.find(".clock-minutes");
      var $seconds = $countdown.find(".clock-seconds");

      function updateTimer() {
        totalSeconds--;
        if (totalSeconds < 0) {
          $days.text("D");
          $hours.text("O");
          $minutes.text("N");
          $seconds.text("E");
          return;
        }
        var days = Math.floor(totalSeconds / (60 * 60 * 24));
        var hours = Math.floor((totalSeconds % (60 * 60 * 24)) / (60 * 60));
        var minutes = Math.floor((totalSeconds % (60 * 60)) / 60);
        var seconds = totalSeconds % 60;

        // Formatear con ceros
        var formattedSeconds = seconds < 10 ? "0" + seconds : seconds;
        var formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
        var formattedHours = hours < 10 ? "0" + hours : hours;

        function animate($el, value) {
          if ($el.text() != value) {
            $el.addClass("clock-updating");
            setTimeout(function () {
              $el.text(value).removeClass("clock-updating");
            }, 150);
          } else {
            $el.text(value);
          }
        }
        animate($days, days);
        animate($hours, formattedHours);
        animate($minutes, formattedMinutes);
        animate($seconds, formattedSeconds);
      }
      updateTimer();
      setInterval(updateTimer, 1000);
    });
  }

  /**
   * Inicializar slider de eventos
   */
  function initSlider() {
    $(".event-show-slider").each(function () {
      var $slider = $(this);
      var $images = $slider.find(".slider-image-item");
      var $infos = $slider.find(".slider-info-item");
      var $indicators = $slider.find(".slider-indicator");
      var currentIndex = 0;
      var totalSlides = $images.length;
      var autoplay = $slider.data("autoplay") === 1;
      var autoplaySpeed = $slider.data("autoplay-speed") || 5000;
      var autoplayInterval;

      if (totalSlides <= 1) {
        $slider.find(".slider-controls, .slider-indicators").hide();
        return;
      }

      // Actualizar posiciones de las imágenes
      function updateSlider() {
        $images.each(function (index) {
          var $img = $(this);
          var diff = index - currentIndex;

          // Limpiar clases
          $img.removeClass("active next far-next prev far-prev");

          // Calcular posición relativa considerando loop
          if (diff === 0) {
            $img.addClass("active");
          } else if (diff === 1 || diff === -(totalSlides - 1)) {
            $img.addClass("next");
          } else if (diff === 2 || diff === -(totalSlides - 2)) {
            $img.addClass("far-next");
          } else if (diff === -1 || diff === totalSlides - 1) {
            $img.addClass("prev");
          } else if (diff === -2 || diff === totalSlides - 2) {
            $img.addClass("far-prev");
          }
        });

        // Actualizar panel de información
        $infos.removeClass("active").eq(currentIndex).addClass("active");
      }

      // Siguiente slide
      function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlider();
      }

      // Anterior slide
      function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateSlider();
      }

      // Ir a slide específico
      function goToSlide(index) {
        currentIndex = index;
        updateSlider();
      }

      // Controles
      $slider.find(".slider-next").on("click", function () {
        nextSlide();
        resetAutoplay();
      });

      $slider.find(".slider-prev").on("click", function () {
        prevSlide();
        resetAutoplay();
      });

      // Click en imagen para ir a ese slide
      $images.on("click", function (e) {
        var $img = $(this);
        if (!$img.hasClass("active")) {
          e.preventDefault();
          var index = $img.data("slide-index");
          goToSlide(index);
          resetAutoplay();
        }
      });

      // Autoplay
      function startAutoplay() {
        if (autoplay) {
          autoplayInterval = setInterval(nextSlide, autoplaySpeed);
        }
      }

      function stopAutoplay() {
        if (autoplayInterval) {
          clearInterval(autoplayInterval);
        }
      }

      function resetAutoplay() {
        stopAutoplay();
        startAutoplay();
      }

      // Pausar en hover
      $slider.on("mouseenter", function () {
        stopAutoplay();
      });

      $slider.on("mouseleave", function () {
        startAutoplay();
      });

      // Soporte para teclado
      $slider.attr("tabindex", "0");
      $slider.on("keydown", function (e) {
        if (e.key === "ArrowRight") {
          nextSlide();
          resetAutoplay();
        } else if (e.key === "ArrowLeft") {
          prevSlide();
          resetAutoplay();
        }
      });

      // Soporte para swipe en móviles
      var touchStartX = 0;
      var touchEndX = 0;

      $slider.on("touchstart", function (e) {
        touchStartX = e.originalEvent.touches[0].clientX;
      });

      $slider.on("touchend", function (e) {
        touchEndX = e.originalEvent.changedTouches[0].clientX;
        handleSwipe();
      });

      function handleSwipe() {
        var swipeThreshold = 50;
        var diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
          if (diff > 0) {
            nextSlide();
          } else {
            prevSlide();
          }
          resetAutoplay();
        }
      }

      // Iniciar
      updateSlider();
      startAutoplay();
    });
  }

  /**
   * Ver asistentes en modal
   */
  var currentEventIdForExport = null;

  $(document).on("click", ".view-attendees-link", function (e) {
    e.preventDefault();

    var eventId = $(this).data("event-id");
    var eventTitle = $(this).data("event-title");

    currentEventIdForExport = eventId;

    // Mostrar modal
    $("#attendees-modal").fadeIn();
    $("#attendees-event-title").text(eventTitle);
    $("#attendees-list-container").html("<p>Cargando...</p>");

    // Obtener asistentes
    $.ajax({
      url: eventShowData.ajaxUrl,
      type: "POST",
      data: {
        action: "event_show_get_attendees",
        event_id: eventId,
        nonce: eventShowData.nonce,
      },
      success: function (response) {
        if (response.success) {
          var attendees = response.data.attendees;
          if (attendees && attendees.length > 0) {
            var html = '<table class="dashboard-table">';
            html += "<thead><tr>";
            html += "<th>Nombre</th>";
            html += "<th>Email</th>";
            html += "<th>Teléfono</th>";
            html += "<th>Cantidad</th>";
            html += "<th>Fecha de Registro</th>";
            html += "</tr></thead>";
            html += "<tbody>";

            attendees.forEach(function (attendee) {
              html += "<tr>";
              html += "<td>" + attendee.name + "</td>";
              html += "<td>" + attendee.email + "</td>";
              html += "<td>" + (attendee.phone || "-") + "</td>";
              html += "<td>" + attendee.num_attendees + "</td>";
              html += "<td>" + attendee.registration_date + "</td>";
              html += "</tr>";
            });

            html += "</tbody></table>";
            $("#attendees-list-container").html(html);
          } else {
            $("#attendees-list-container").html(
              "<p>No hay asistentes registrados.</p>"
            );
          }
        } else {
          $("#attendees-list-container").html(
            "<p>Error al cargar asistentes.</p>"
          );
        }
      },
      error: function () {
        $("#attendees-list-container").html(
          "<p>Error al cargar asistentes.</p>"
        );
      },
    });
  });

  /**
   * Exportar asistentes desde modal
   */
  $(document).on("click", "#export-attendees-csv", function (e) {
    e.preventDefault();

    if (!currentEventIdForExport) {
      alert("Error: No se pudo determinar el evento");
      return;
    }

    var url =
      eventShowData.ajaxUrl +
      "?action=event_show_export_attendees&event_id=" +
      currentEventIdForExport +
      "&nonce=" +
      eventShowData.nonce;
    window.location.href = url;
  });
})(jQuery);
