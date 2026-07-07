window.addEventListener("load", () => {
    fetch("../app/objects/Disruption.php?fetchDisruptions=true")
    .then(res => res.json())
    .then(data => {
        const script = document.createElement("script");
        for (const line of data) script.textContent += line + "\n";
        document.body.append(script);
    });
})