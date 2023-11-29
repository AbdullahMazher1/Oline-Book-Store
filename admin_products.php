<?php

include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];
$logFile = 'logfile.txt';

function logMessage($message)
{
    global $logFile;
    $fileHandle = fopen($logFile, 'a') or die("Can't open file");
    fwrite($fileHandle, $message . '  ' . date('Y-m-d H:i:s') . "\n");
    fclose($fileHandle);
}


if(!isset($admin_id)){
   header('location:login.php');
};

// Add Products
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image = $_FILES['image']['name'];
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'uploaded_img/' . $image;

    // Check if the product name already exists but is marked as 'Inactive'
    $existing_product_query = mysqli_query($conn, "SELECT id, status FROM `products` WHERE name = '$name'") or die('query failed');

    if (mysqli_num_rows($existing_product_query) > 0) {
        $existing_product = mysqli_fetch_assoc($existing_product_query);

        if ($existing_product['status'] === 'Inactive') {
            // Re-activate the previously deleted product
            mysqli_query($conn, "UPDATE `products` SET status = 'Active', author = '$author', price = '$price', stock = '$stock', image = '$image_folder', updated_at = CURRENT_TIMESTAMP WHERE id = '{$existing_product['id']}'") or die('query failed');

            if ($image_size > 2000000) {
                $message[] = 'Image size is too large';
            } else {
                move_uploaded_file($image_tmp_name, $image_folder);
                $message[] = 'Product reactivated successfully!';
            }
        } else {
            $message[] = 'Product with the same name already exists!';
        }
    } else {
        // Insert the new product
        $add_product_query = mysqli_query($conn, "INSERT INTO `products`(name, author, price, stock, image, status, created_at, updated_at) VALUES('$name', '$author', '$price', '$stock', '$image_folder', 'Active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)") or die('query failed');

        if ($add_product_query) {
            if ($stock == 0) {
                $message[] = 'Out of stock!';
                // Update status to 'Inactive' for products with zero stock
                mysqli_query($conn, "UPDATE `products` SET status = 'Inactive' WHERE stock = 0") or die('query failed');
            }

            if ($image_size > 2000000) {
                $message[] = 'Image size is too large';
            } else {
                move_uploaded_file($image_tmp_name, $image_folder);
                $message[] = 'Product added successfully!';
            }
        } else {
            $message[] = 'Product could not be added!';
        }
    }
}




/*Deletion*/

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    // Fetch the image filename associated with the product
    $delete_image_query = mysqli_query($conn, "SELECT image FROM `products` WHERE id = '$delete_id'") or die('query failed');
    $fetch_delete_image = mysqli_fetch_assoc($delete_image_query);
    $image_filename = $fetch_delete_image['image'];

    // Remove the image file from the server directory
    unlink('uploaded_img/' . $image_filename);

    // Update the status of the product to 'Inactive' in the database
    mysqli_query($conn, "UPDATE `products` SET status = 'Inactive' WHERE id = '$delete_id'") or die('query failed');

    // Redirect to the admin_products.php page after deletion
    header('location:admin_products.php'); 
}

/*Update*/

if (isset($_POST['update_product'])) {
    $update_p_id = $_POST['update_p_id'];
    $update_name = mysqli_real_escape_string($conn, $_POST['update_name']);
    $update_author = mysqli_real_escape_string($conn, $_POST['update_author']);
    $update_price = $_POST['update_price'];
    $update_stock = $_POST['update_stock'];

    // Fetch the existing image to manage it later
    $update_image_query = mysqli_query($conn, "SELECT image FROM `products` WHERE id = '$update_p_id'") or die('query failed');
    $fetch_update_image = mysqli_fetch_assoc($update_image_query);
    $update_old_image = $fetch_update_image['image'];

    // Check for a new image upload
    if (!empty($_FILES['update_image']['name'])) {
        $update_image = $_FILES['update_image']['name'];
        $update_image_tmp_name = $_FILES['update_image']['tmp_name'];
        $update_image_size = $_FILES['update_image']['size'];
        $update_folder = 'uploaded_img/' . $update_image;

        if ($update_image_size > 2000000) {
            $message[] = 'Image file size is too large';
        } else {
            // Update the product details including the new image name
            mysqli_query($conn, "UPDATE `products` SET name = '$update_name', author = '$update_author', price = '$update_price', stock = '$update_stock', image = '$update_image', updated_at = CURRENT_TIMESTAMP WHERE id = '$update_p_id'") or die('query failed');
            move_uploaded_file($update_image_tmp_name, $update_folder);

            // Remove the old image file
            unlink('uploaded_img/' . $update_old_image);
            $message[] = 'Product updated successfully!';
        }
    } else {
        // If no new image is uploaded, update product details without changing the image
        mysqli_query($conn, "UPDATE `products` SET name = '$update_name', author = '$update_author', price = '$update_price', stock = '$update_stock', updated_at = CURRENT_TIMESTAMP WHERE id = '$update_p_id'") or die('query failed');
        $message[] = 'Product updated successfully!';
    }

    header('location:admin_products.php');
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>products</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/admin_style.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<!-- product CRUD section starts  -->

<section class="add-products">

   <h1 class="title">Shop Products</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <h3>Add Product</h3>
      <input type="text" name="name" class="box" placeholder="Enter product name" required>
      <input type="text" name="author" class="box" placeholder="Enter author" required> 
      <input type="number" min="5" name="price" class="box" placeholder="Enter product price" required>
      <input type="number" min="10" name="stock" class="box" placeholder="Enter product stock" required> 
      <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" class="box" required>
      <input type="submit" value="Add Product" name="add_product" class="addbtn">
   </form>

</section>

<!-- product CRUD section ends -->

<!-- show products -->
<section class="show-products">
   <div class="box-container">
      <?php
         $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE status = 'Active'") or die('query failed');
         if(mysqli_num_rows($select_products) > 0){
            while($fetch_products = mysqli_fetch_assoc($select_products)){
               $stock = $fetch_products['stock'];
               $productId = $fetch_products['id'];

               // Check if stock is zero
               if ($stock == 0) {
                   // Update status to 'Inactive' for products with zero stock
                   mysqli_query($conn, "UPDATE `products` SET status = 'Inactive' WHERE id = '$productId'") or die('query failed');
                   echo '<div class="box">';
                   echo '<img src="' . $fetch_products['image'] . '" alt="Product Image">';
                   echo '<div class="name">' . $fetch_products['name'] . '</div>';
                   echo '<div class="author">Author: ' . $fetch_products['author'] . '</div>';
                   echo '<div class="price">$' . $fetch_products['price'] . '/-</div>';
                   echo '<div class="stock">Out of stock</div>';
                   echo '<a href="admin_products.php?update=' . $fetch_products['id'] . '" class="option-btn">Update</a>';
                  echo '<a href="admin_products.php?delete=' . $fetch_products['id'] . '" class="delete-btn" onclick="return confirm(\'Delete this product?\');">Delete</a>';
                   echo '</div>';
               } else {
                   // Display products with available stock
                   echo '<div class="box">';
                   echo '<img src="' . $fetch_products['image'] . '" alt="Product Image">';
                   echo '<div class="name">' . $fetch_products['name'] . '</div>';
                   echo '<div class="author">Author: ' . $fetch_products['author'] . '</div>';
                   echo '<div class="price">$' . $fetch_products['price'] . '/-</div>';
                   echo '<div class="stock">Stock: ' . $stock . '</div>';
                   echo '<a href="admin_products.php?update=' . $fetch_products['id'] . '" class="update-btn">Update</a>';
                   echo '<a href="admin_products.php?delete=' . $fetch_products['id'] . '" class="delete-btn" onclick="return confirm(\'Delete this product?\');">Delete</a>';
                   echo '</div>';
               }
            }
         } else {
            echo '<p class="empty">No active products available!</p>';
         }
      ?>
   </div>
</section>

<!--Edit a book-->

<section class="edit-product-form">
    <?php
    if (isset($_GET['update'])) {
        $update_id = $_GET['update'];
        $update_query = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$update_id'") or die('Query failed');
        if (mysqli_num_rows($update_query) > 0) {
            $fetch_update = mysqli_fetch_assoc($update_query);
            ?>
            <!-- Update Product Form -->
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="update_p_id" value="<?php echo $fetch_update['id']; ?>">
                <input type="hidden" name="update_old_image" value="<?php echo $fetch_update['image']; ?>">
                <img src="uploaded_img/<?php echo $fetch_update['image']; ?>" alt="">
                <input type="text" name="update_name" value="<?php echo $fetch_update['name']; ?>" class="box"
                       required placeholder="Enter product name">
                <input type="text" name="update_author" value="<?php echo $fetch_update['author']; ?>"
                       class="box" required placeholder="Enter author">
                <input type="number" name="update_price" value="<?php echo $fetch_update['price']; ?>" min="0"
                       class="box" required placeholder="Enter product price">
                <input type="number" name="update_stock" value="<?php echo $fetch_update['stock']; ?>" min="0"
                       class="box" required placeholder="Enter product stock">
                <input type="file" class="box" name="update_image" accept="image/jpg, image/jpeg, image/png">
                <input type="submit" value="Update" name="update_product" class="update-btn">
                <input type="reset" value="Cancel" id="close-update" class="option-btn">
            </form>
            <!-- End of Update Product Form -->
            <?php
        }
    } else {
        //echo '<p>No product found for update.</p>';
        echo '<script>document.querySelector(".edit-product-form").style.display ="none";</script>';
    }
    ?>
</section>


<!-- custom admin js file link -->
<script src="js/admin_script.js"></script> 

</body>
</html>
