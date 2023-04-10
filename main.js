//############## SELECT LOGICAL MODELS
const models = [
 { model: "mux", size: ["4×1", "8×1", "16×1", "32×1"] },
 { model: "demux", size: ["1×4", "1×8", "1×16", "1×32"] },
 { model: "decoder", size: ["2×4", "3×8", "4×16", "8×32"] },
 { model: "encoder", size: ["4×2", "8×3", "16×4", "32×8"] },
 { model: "comparator", size: ["2×3"] },
 { model: "adder_subtractor", size: ["3×2"] },
];
models.forEach((element) => {
 document.querySelector(
  "#model"
 ).innerHTML += `<option value="${element.model}">${element.model}</option>`;
});

//############# SELECT SIZE OF THE MODEL
function selectSize() {
 const selectBox = document.getElementById("model");
 const selectedValue = selectBox.options[selectBox.selectedIndex].value;
 models.forEach((element) => {
  if (element.model == selectedValue) {
   if (
    selectedValue == "mux" ||
    selectedValue == "demux" ||
    selectedValue == "comparator" ||
    selectedValue == "adder_subtractor"
   ) {
    document.getElementById("type").innerHTML = `
    <option value="" disabled>Select The Data Type</option>
    <option value="std_logic" >std_logic</option>
    <option value="std_logic_vector(0 upto 3)">std_logic_vector(0 upto 3)</option>
    <option value="std_logic_vector(0 upto 7)">std_logic_vector(0 upto 7)</option>
    <option value="std_logic_vector(0 upto 15)">std_logic_vector(0 upto 15)</option><option value="std_logic_vector(0 upto 31)">std_logic_vector(0 upto 31)</option>`;
   } else {
    document.getElementById("type").innerHTML = `
    <option value="std_logic_vector">std_logic_vector</option>`;
   }
   document.querySelector(
    "#size"
   ).innerHTML = `<option value="" disabled>Select The Size</option></select
 >`;
   element.size.forEach((size) => {
    document.querySelector(
     "#size"
    ).innerHTML += `<option value="${size}">${size}</option>`;
   });
  }
 });
}
selectSize(); //call the function when reload

//############ submitting the form
const form = document.getElementById("my-form");
form.addEventListener("submit", (event) => {
 event.preventDefault();
 const formData = new FormData(form);
 const jsonData = JSON.stringify(Object.fromEntries(formData));
 fetch("file.php", {
  method: "POST",
  headers: {
   "Content-Type": "application/json",
  },
  body: jsonData,
 })
  .then((response) => response.text())
  .then((data) => {
   document.querySelector(".text .content").classList.add("show");
   document.querySelector(".text .buttons").classList.add("show");
   document.querySelector(".text .content").innerHTML = `  
      <pre>${data}</pre>
      `;
  })
  .catch((error) => {
   console.error(error);
  });
});

//############# EXPORT THE CODE IN MYFILE.TXT
function export_code() {
 const text = document.querySelector("pre").textContent;
 const filename = "myfile.txt";
 const blob = new Blob([text], { type: "text/plain;charset=utf-8" });
 const anchor = document.createElement("a");
 anchor.download = filename;
 anchor.href = URL.createObjectURL(blob);
 anchor.click();
 URL.revokeObjectURL(anchor.href);
}
//########## COPY THE CODE TO CLIPBOARD
function copy_code() {
 const text = document.querySelector("pre").textContent;
 navigator.clipboard.writeText(text);
}

function decimalToBinary(decimal) {
 let binary = "";
 while (decimal > 0) {
  binary = (decimal % 2) + binary;
  decimal = Math.floor(decimal / 2);
 }
 return binary === "" ? "0" : binary;
}
decimalToBinary(10);
