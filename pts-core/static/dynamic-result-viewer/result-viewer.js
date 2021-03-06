function delete_result_file(id)
{
	if(confirm("Really delete the '" + id + "' result file permanently?"))
	{
		window.location.href = "/index.php?remove_result=" + id;
	}
}
function edit_result_file_meta()
{
	 document.getElementById("result_file_title").contentEditable = "true";
	 document.getElementById("result_file_desc").contentEditable = "true";
	 document.getElementById("result_file_title").style.border = "1px solid #AAA";
	 document.getElementById("result_file_desc").style.border = "1px solid #AAA";
	 document.getElementById("edit_result_file_meta_button").style.display = "none";
	 document.getElementById("save_result_file_meta_button").style.display = "inline";
}
function save_result_file_meta(id)
{
	var title = document.getElementById("result_file_title").textContent;
	var description = document.getElementById("result_file_desc").textContent;
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	if(this.readyState == 4 && this.status == 200) {
		location.reload();
		}
	};
	xhttp.open("POST", "/index.php?page=update-result-file-meta", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + id + "&result_title=" + title + "&result_desc=" + description);
}
function delete_result_from_result_file(result_file, result_hash)
{
	if(confirm("Permanently delete this result graph?"))
	{
		document.getElementById("result-" + result_hash).style.display = "none";
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {

			}
		};
		xhttp.open("POST", "/index.php?page=remove-result-object", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("result_file_id=" + result_file + "&result_object=" + result_hash);
	}
	return false;
}
function display_add_annotation_for_result_object(result_file, result_hash, link_obj)
{
	link_obj.style.display = "none";
	document.getElementById("annotation_area_" + result_hash).style.display = "inline";
}
function add_annotation_for_result_object(result_file, result_hash, form)
{
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	if(this.readyState == 4 && this.status == 200) {
		location.reload();
		}
	};
	xhttp.open("POST", "/index.php?page=add-annotation-to-result-object", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + result_file + "&result_object=" + result_hash + "&annotation=" + form.annotation.value);
}
function update_annotation_for_result_object(result_file, result_hash)
{
	var annotation_updated = document.getElementById("update_annotation_" + result_hash).textContent;
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
	if(this.readyState == 4 && this.status == 200) {
		location.reload();
		}
	};
	xhttp.open("POST", "/index.php?page=add-annotation-to-result-object", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("result_file_id=" + result_file + "&result_object=" + result_hash + "&annotation=" + annotation_updated);
}
