window.addEventListener("load", () => {
  console.log("Página cargada completamente"); 
  const loader = document.getElementById("loader");
  if (loader) {
    loader.style.opacity = 0;
    setTimeout(() => {
      loader.style.display = "none";
    }, 500);
  }
});
