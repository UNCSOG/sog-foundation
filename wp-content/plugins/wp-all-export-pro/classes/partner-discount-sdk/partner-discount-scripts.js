// Add animation to elements when they come into view
document.addEventListener("DOMContentLoaded", () => {
  // Check if IntersectionObserver is supported
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.style.animationPlayState = "running"
            observer.unobserve(entry.target)
          }
        })
      },
      {
        threshold: 0.1,
        rootMargin: "50px",
      },
    )

    // Observe all grid items
    document.querySelectorAll(".soflyy_pd_sdk-grid-item").forEach((item) => {
      observer.observe(item)
    })
  } else {
    // Fallback for browsers that don't support IntersectionObserver
    document.querySelectorAll(".soflyy_pd_sdk-grid-item").forEach((item) => {
      item.style.animationPlayState = "running"
    })
  }

  // Copy discount code to clipboard when clicked - fixed implementation
  document.querySelectorAll(".soflyy_pd_sdk-partner-code code").forEach((codeElement) => {
    codeElement.addEventListener("click", function () {
      const code = this.textContent.trim()
      const originalText = this.dataset.originalText || code

      // Use the modern clipboard API
      navigator.clipboard
        .writeText(code)
        .then(() => {
          // Change the text to "Copied!"
          this.textContent = "Copied!"
          this.classList.add("copied")

          // Restore the original text after 2 seconds
          setTimeout(() => {
            this.textContent = originalText
            this.classList.remove("copied")
          }, 2000)
        })
        .catch((err) => {
          // Fallback for older browsers
          const textArea = document.createElement("textarea")
          textArea.value = code
          textArea.style.position = "fixed" // Avoid scrolling to bottom
          document.body.appendChild(textArea)
          textArea.focus()
          textArea.select()

          try {
            document.execCommand("copy")
            this.textContent = "Copied!"
            this.classList.add("copied")

            setTimeout(() => {
              this.textContent = originalText
              this.classList.remove("copied")
            }, 2000)
          } catch (err) {
            console.error("Failed to copy: ", err)
          }

          document.body.removeChild(textArea)
        })
    })
  })
})
