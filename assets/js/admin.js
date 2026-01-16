/**
 * JavaScript de administración del plugin Event Show
 *
 * @package Event_Show
 */

(function ($) {
  "use strict";

  /**
   * Inicializar cuando el DOM esté listo
   */
  $(document).ready(function () {
    // Datepicker
    initDateTimePicker();

    // Media Uploader
    initMediaUploader();

    // Exportar asistentes
    initExportAttendees();

    // Eliminar asistente
    initDeleteAttendee();

    // Validación de fechas
    initDateValidation();

    // Imagen organizador/lugar
    initTaxonomyImage();
  });

  /**
   * Inicializar datepicker y timepicker
   */
  function initDateTimePicker() {
    if (typeof $.fn.datepicker !== "undefined") {
      $("#_event_start_date, #_event_end_date").datepicker({
        dateFormat: "dd/mm/yy",
        changeMonth: true,
        changeYear: true,
        minDate: 0,
        onSelect: function (dateText, inst) {
          validateEventDates();
        },
      });
    }

    // Timepicker - solo si está disponible
    if (typeof $.fn.timepicker !== "undefined") {
      $("#_event_start_time, #_event_end_time").timepicker({
        timeFormat: "HH:mm",
        interval: 15,
        minTime: "00:00",
        maxTime: "23:45",
        defaultTime: "09:00",
        startTime: "00:00",
        dynamic: false,
        dropdown: true,
        scrollbar: true,
      });
    } else {
      // Si timepicker no está disponible, usar input type="time" HTML5
      $("#_event_start_time, #_event_end_time").attr("type", "time");
    }
  }

  /**
   * Inicializar media uploader
   */
  function initMediaUploader() {
    var mediaUploader;

    // Imagen miniatura del evento (para grid)
    $(document).on("click", ".event-upload-thumbnail", function (e) {
      e.preventDefault();

      mediaUploader = wp.media.frames.file_frame = wp.media({
        title: "Seleccionar miniatura del evento",
        button: {
          text: "Usar esta imagen",
        },
        multiple: false,
      });

      mediaUploader.on("select", function () {
        var attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        $("#event_thumbnail").val(attachment.id);
        $(".event-thumbnail-preview").html(
          '<img src="' +
            attachment.url +
            '" style="max-width: 100%; height: auto;">'
        );
        $(".event-remove-thumbnail").show();
      });

      mediaUploader.open();
    });

    // Remover miniatura del evento
    $(document).on("click", ".event-remove-thumbnail", function (e) {
      e.preventDefault();
      $("#event_thumbnail").val("");
      $(".event-thumbnail-preview").html(
        '<p class="description">No hay miniatura seleccionada</p>'
      );
      $(this).hide();
    });

    // Galería del evento
    $("#upload_event_gallery_button").on("click", function (e) {
      e.preventDefault();

      var galleryUploader = (wp.media.frames.file_frame = wp.media({
        title: "Seleccionar galería del evento",
        button: {
          text: "Usar estas imágenes",
        },
        multiple: true,
      }));

      galleryUploader.on("select", function () {
        var selection = galleryUploader.state().get("selection");
        var galleryIds = [];
        var galleryHtml = "";

        selection.map(function (attachment) {
          attachment = attachment.toJSON();
          galleryIds.push(attachment.id);
          galleryHtml +=
            '<div class="gallery-image" data-id="' + attachment.id + '">';
          galleryHtml +=
            '<img src="' + attachment.url + '" style="max-width: 100px;">';
          galleryHtml += '<span class="remove-gallery-image">&times;</span>';
          galleryHtml += "</div>";
        });

        $("#_event_gallery").val(galleryIds.join(","));
        $("#event_gallery_preview").html(galleryHtml);
      });

      galleryUploader.open();
    });

    // Remover imagen de galería
    $(document).on("click", ".remove-gallery-image", function () {
      var $image = $(this).parent();
      var imageId = $image.data("id");
      var galleryIds = $("#_event_gallery").val().split(",");

      galleryIds = galleryIds.filter(function (id) {
        return id != imageId;
      });

      $("#_event_gallery").val(galleryIds.join(","));
      $image.remove();
    });
  }

  /**
   * Inicializar exportación de asistentes
   */
  function initExportAttendees() {
    // Ya no se necesita código aquí porque ahora se usa un enlace directo a la página de asistentes
  }

  /**
   * Inicializar eliminación de asistente
   */
  function initDeleteAttendee() {
    $(".delete-attendee-btn").on("click", function (e) {
      e.preventDefault();

      if (!confirm("¿Estás seguro de que quieres eliminar este asistente?")) {
        return;
      }

      var $btn = $(this);
      var attendeeId = $btn.data("attendee-id");
      var nonce = $btn.data("nonce");

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "event_show_delete_attendee",
          attendee_id: attendeeId,
          nonce: nonce,
        },
        success: function (response) {
          if (response.success) {
            $btn.closest("tr").fadeOut(function () {
              $(this).remove();
            });
          } else {
            alert(response.data.message);
          }
        },
      });
    });
  }

  /**
   * Validación de fechas
   */
  function initDateValidation() {
    $("#post").on("submit", function (e) {
      if (!validateEventDates()) {
        e.preventDefault();
        alert("La fecha de inicio no puede ser posterior a la fecha de fin.");
        return false;
      }
    });
  }

  function validateEventDates() {
    var startDate = $("#_event_start_date").val();
    var endDate = $("#_event_end_date").val();

    if (!startDate || !endDate) {
      return true;
    }

    var start = parseDate(startDate);
    var end = parseDate(endDate);

    if (start > end) {
      $("#_event_end_date").css("border-color", "red");
      return false;
    } else {
      $("#_event_end_date").css("border-color", "");
      return true;
    }
  }

  function parseDate(dateString) {
    var parts = dateString.split("/");
    return new Date(parts[2], parts[1] - 1, parts[0]);
  }

  /**
   * Inicializar imagen de taxonomía
   */
  function initTaxonomyImage() {
    var taxMediaUploader;

    // Upload imagen - Organizador
    $(document).on("click", ".organizador-upload-image", function (e) {
      e.preventDefault();

      var $btn = $(this);

      taxMediaUploader = wp.media.frames.file_frame = wp.media({
        title: "Seleccionar imagen del organizador",
        button: {
          text: "Usar esta imagen",
        },
        multiple: false,
      });

      taxMediaUploader.on("select", function () {
        var attachment = taxMediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        $("#organizador_image").val(attachment.id);
        $(".organizador-image-preview").html(
          '<img src="' +
            attachment.url +
            '" style="max-width: 150px; height: auto; margin-top: 10px;">'
        );
        $(".organizador-remove-image").show();
      });

      taxMediaUploader.open();
    });

    // Remover imagen - Organizador
    $(document).on("click", ".organizador-remove-image", function (e) {
      e.preventDefault();
      $("#organizador_image").val("");
      $(".organizador-image-preview").html("");
      $(this).hide();
    });

    // Upload imagen - Lugar
    $(document).on("click", ".lugar-upload-image", function (e) {
      e.preventDefault();

      var $btn = $(this);

      taxMediaUploader = wp.media.frames.file_frame = wp.media({
        title: "Seleccionar imagen del lugar",
        button: {
          text: "Usar esta imagen",
        },
        multiple: false,
      });

      taxMediaUploader.on("select", function () {
        var attachment = taxMediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        $("#lugar_image").val(attachment.id);
        $(".lugar-image-preview").html(
          '<img src="' +
            attachment.url +
            '" style="max-width: 150px; height: auto; margin-top: 10px;">'
        );
        $(".lugar-remove-image").show();
      });

      taxMediaUploader.open();
    });

    // Remover imagen - Lugar
    $(document).on("click", ".lugar-remove-image", function (e) {
      e.preventDefault();
      $("#lugar_image").val("");
      $(".lugar-image-preview").html("");
      $(this).hide();
    });

    // Upload imagen - Genérico (para otras taxonomías)
    $(document).on("click", ".tax-image-upload", function (e) {
      e.preventDefault();

      var $btn = $(this);

      taxMediaUploader = wp.media.frames.file_frame = wp.media({
        title: "Seleccionar imagen",
        button: {
          text: "Usar esta imagen",
        },
        multiple: false,
      });

      taxMediaUploader.on("select", function () {
        var attachment = taxMediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        $btn.siblings(".tax-image-id").val(attachment.id);
        $btn
          .siblings(".tax-image-preview")
          .html('<img src="' + attachment.url + '" style="max-width: 150px;">');
        $btn.siblings(".tax-image-remove").show();
      });

      taxMediaUploader.open();
    });

    // Remover imagen - Genérico
    $(document).on("click", ".tax-image-remove", function (e) {
      e.preventDefault();
      $(this).siblings(".tax-image-id").val("");
      $(this).siblings(".tax-image-preview").html("");
      $(this).hide();
    });
  }

  /**
   * Actualizar contador de capacidad
   */
  $("#_event_capacity").on("input", function () {
    var capacity = parseInt($(this).val());
    if (capacity > 0) {
      $(".capacity-info").text("Capacidad máxima: " + capacity + " asistentes");
    }
  });

  /**
   * Toggle opciones de registro
   */
  $("#_event_enable_registration")
    .on("change", function () {
      if ($(this).is(":checked")) {
        $(".registration-options").slideDown();
      } else {
        $(".registration-options").slideUp();
      }
    })
    .trigger("change");
})(jQuery);
