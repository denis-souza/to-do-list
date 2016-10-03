function getTask(uuid) {
  $.ajax({
    type: 'GET',
    url: 'services/getTask',
    encode: true,
    data: {'uuid':uuid},
    dataType: 'json',
    success: function(data) {
      $('.modal-title').html('Edit Task');
      $('.button-submit').data('data-action', 'updateTask');

      $(data).each(function(i, task) {

        $('#content').val(task.content);
        $('#type').val(task.type);
        $('#sort_order').val(task.sort_order);
        $('#has-done').prop('checked', parseInt(task.done));
        $('.button-submit').attr('data-uuid', task.uuid);
      });
    },
  });
}

function deleteTask(uuid) {

  $.ajax({
    type: 'DELETE',
    url: 'services/deleteTask',
    encode: true,
    data: JSON.stringify({'uuid' : uuid}),
    dataType: 'json',
    success: function(data){

      // Added success message.
      $('.msg').append('<div class="alert alert-success"> <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>' + data.msg + '</div>');

      // Updated task list.
      listTask();
    }
  });
}

function insertTask() {
  var formData = {
    'content' : $('#content').val(),
    'type' : $('#type').val(),
    'sort_order' : $('#sort_order').val(),
    'has_done' : $('#has-done').prop('checked')
  }

  $.ajax({
    type: 'POST',
    url: 'services/insertTask',
    data: JSON.stringify(formData),
    dataType: 'json',
    encode: true,
    success: function(data){

      // Close the modal.
      $('#modalForm').modal('toggle');

      // Added success message.
      $('.msg').append('<div class="alert alert-success"> <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>' + data.msg + '</div>');

      // Updated task list.
      listTask();
    },
    complete: function(response, textStatus) {
      if (response.status == 406) {
        var responseText = jQuery.parseJSON(response.responseText);

        $('.modal-body').prepend('<div class="alert alert-danger"> <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>' + responseText.msg + '</div>');
      }
    },
  });
}

function updateTask(uuid) {
  var formData = {
    'content':$('#content').val(),
    'type' : $('#type').val(),
    'sort_order' : $('#sort_order').val(),
    'has_done' : $('#has-done').prop('checked'),
    'uuid': uuid
  }

  $.ajax({
    type: 'POST',
    url: 'services/updateTask',
    data: JSON.stringify(formData),
    dataType: 'json',
    encode: true,
    success: function(data){

      listTask();
      // Added success message.
      $('.msg').append('<div class="alert alert-success"> <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>' + data.msg + '</div>');

      // Close the modal.
      $('#modalForm').modal('toggle');
    },
    complete: function(response, textStatus) {
      if (response.status == 406) {
        var responseText = jQuery.parseJSON(response.responseText);

        $('.modal-body').prepend('<div class="alert alert-danger"> <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>' + responseText.msg + '</div>');
      }
    },
  });
}

function listTask() {
  $.ajax({
    type: 'GET',
    url: 'services/listTask',
    encode: true,
    complete: function(response, textStatus) {
      if (response.status == 204) {
        $('.msg').append('<div class="alert alert-success"> <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>Wow. You have nothing else to do. Enjoy the rest of your day!</div>');
      }
      else if (response.status == 200) {
        var tasks = jQuery.parseJSON(response.responseText);
        var html = '';
        var contentTable = '';

        $(tasks).each(function(i, task){
          html = '<tr><td>' + task.content + '</td>';
          html += '<td>' + task.type + '</td>';
          html += '<td>' + task.date_created + '</td>';
          html += '<td><a href="#" data-uuid="' + task.uuid + '" class="btn action-update" data-toggle="modal" data-target="#modalForm">&nbsp;<i class="glyphicon glyphicon-edit"></i>&nbsp; Edit</a><a href="#" data-uuid="' + task.uuid + '" class="btn action-delete">&nbsp;<i class="glyphicon glyphicon-trash"></i>&nbsp; Delete</a></td>';

          contentTable = contentTable.concat(html);
        });

        $('.table > tbody').html(contentTable);
        $('.table > tbody .action-delete').on('click', function(e) {
          deleteTask($(this).data("uuid"));
          e.preventDefault();
        });

        $('.table > tbody .action-update').on('click', function(e) {
          getTask($(this).data("uuid"));

          e.preventDefault();
        });
      }
    },
  });
}

$(document).ready(function() {

  // Load registers.
  listTask();

  $('.add-task').on('click', function() {
    $('.button-submit').data('data-action', 'insertTask');

    // Clean form fields.
    $('#content').val('');
    $('#type').val('');
    $('#sort_order').val('');
    $('#has-done').prop('checked', 0);
    $('.button-submit').attr('data-uuid', '');
  });

  $('#save-task').on('submit', function(e) {
    var action = $('.button-submit').data('data-action');

    if (action == 'insertTask') {
      insertTask()
    }
    else {
      updateTask($('.button-submit').attr('data-uuid'));
    }

    e.preventDefault();
  });
});
