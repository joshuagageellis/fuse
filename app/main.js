(function() {
	const btn = document.querySelector("#weci");
	const btnReset = document.querySelector("#weci-reset");
	const statusBar = document.querySelector(".weci-status__inner");

	const fetchRequest = async (end) => {
		const response = await fetch(end);
		const data = await response.json();
		return data;
	};

	const updateStatus = (node, data) => {
		node.style.width = (data.count / data.total) * 100 + "%";
	};

	const process = async (end, stat) => {
		fetchRequest(end)
			.then((data) => {
				updateStatus(stat, data);
				if (data.count < data.total) {
					process(end, stat);
				}
			})
			.catch((e) => {
				console.log(e);
			});
	};

	btn.addEventListener("click", function() {
		console.log("clicked");
		const end = this.dataset.target;
		btn.setAttribute("disabled", "disabled");
		process(end, statusBar);
	});

	btnReset.addEventListener("click", function () {
		console.log("clicked");
		const end = this.dataset.target;

		fetchRequest(end)
			.then((data) => {
				console.log(data);
				btn.removeAttribute("disabled");
				statusBar.style.width = 0;
			}
		);
	});
})();