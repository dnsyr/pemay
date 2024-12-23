CREATE OR REPLACE PROCEDURE UpdateLayananMedis (
    p_id IN VARCHAR2,
    p_tanggal IN TIMESTAMP,
    p_totalBiaya IN NUMBER,
    p_description IN VARCHAR2,
    p_status IN VARCHAR2,
    p_jenisLayanan IN ArrayJenisLayananMedis,
    p_pegawai_id IN VARCHAR2,
    p_hewan_id IN VARCHAR2
) AS
BEGIN
    -- Update data layanan medis
    UPDATE LayananMedis
    SET Tanggal = p_tanggal,
        TotalBiaya = p_totalBiaya,
        Description = p_description,
        Status = p_status,
        JenisLayanan = p_jenisLayanan,
        Pegawai_ID = p_pegawai_id,
        Hewan_ID = p_hewan_id,
        onDelete = 0
    WHERE ID = p_id;
    
    -- Jika tidak ada baris yang terupdate, throw exception
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Data layanan medis tidak ditemukan atau sudah dihapus');
    END IF;
END UpdateLayananMedis;
/

-- Tambahkan trigger untuk memastikan total biaya sesuai dengan jenis layanan
CREATE OR REPLACE TRIGGER trg_update_layananmedis_biaya
BEFORE UPDATE ON LayananMedis
FOR EACH ROW
DECLARE
    v_total_biaya NUMBER := 0;
    v_biaya NUMBER;
    v_jenis_id VARCHAR2(36);
BEGIN
    IF :NEW.JenisLayanan IS NOT NULL THEN
        FOR i IN 1..:NEW.JenisLayanan.COUNT LOOP
            v_jenis_id := :NEW.JenisLayanan(i);
            SELECT Biaya INTO v_biaya
            FROM JenisLayananMedis
            WHERE ID = v_jenis_id AND onDelete = 0;
            v_total_biaya := v_total_biaya + v_biaya;
        END LOOP;
        
        -- Update total biaya
        :NEW.TotalBiaya := v_total_biaya;
    END IF;
END;
/ 

-- Procedure to Read all records from Penjualan
CREATE OR REPLACE PROCEDURE SelectAllPenjualan (
    p_search IN VARCHAR2 DEFAULT NULL,
    p_start_date IN DATE DEFAULT NULL,
    p_end_date IN DATE DEFAULT NULL,
    p_offset IN NUMBER DEFAULT 0,
    p_limit IN NUMBER DEFAULT 10,
    p_total_rows OUT NUMBER,
    p_result_cursor OUT SYS_REFCURSOR
)
AS
BEGIN
    -- Count total rows
    SELECT COUNT(*) INTO p_total_rows
    FROM PENJUALAN PJ
    LEFT JOIN Pegawai P ON PJ.PEGAWAI_ID = P.ID
    LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
    WHERE PJ.onDelete = 0
    AND (p_search IS NULL 
        OR UPPER(P.NAMA) LIKE '%' || UPPER(p_search) || '%'
        OR UPPER(PH.NAMA) LIKE '%' || UPPER(p_search) || '%')
    AND (p_start_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) >= TRUNC(p_start_date))
    AND (p_end_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) <= TRUNC(p_end_date));

    -- Open cursor for results
    OPEN p_result_cursor FOR
        SELECT 
            PJ.ID,
            PJ.TANGGALTRANSAKSI,
            PJ.TOTAL_BIAYA as TOTALHARGA,
            P.NAMA as PEGAWAI_NAMA,
            PH.NAMA as PEMILIK_NAMA,
            LISTAGG(PR.NAMA, ', ') WITHIN GROUP (ORDER BY PR.NAMA) as PRODUK_INFO
        FROM PENJUALAN PJ
        LEFT JOIN Pegawai P ON PJ.PEGAWAI_ID = P.ID
        LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
        LEFT JOIN TABLE(PJ.PRODUK) TP ON 1=1
        LEFT JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
        WHERE PJ.onDelete = 0
        AND (p_search IS NULL 
            OR UPPER(P.NAMA) LIKE '%' || UPPER(p_search) || '%'
            OR UPPER(PH.NAMA) LIKE '%' || UPPER(p_search) || '%')
        AND (p_start_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) >= TRUNC(p_start_date))
        AND (p_end_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) <= TRUNC(p_end_date))
        GROUP BY 
            PJ.ID,
            PJ.TANGGALTRANSAKSI,
            PJ.TOTAL_BIAYA,
            P.NAMA,
            PH.NAMA
        ORDER BY PJ.TANGGALTRANSAKSI DESC
        OFFSET p_offset ROWS FETCH NEXT p_limit ROWS ONLY;
END;
/

-- Procedure to Read a record by ID from Penjualan
CREATE OR REPLACE PROCEDURE SelectPenjualanByID(
    p_ID IN VARCHAR2,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT 
        PJ.ID, 
        PJ.TANGGALTRANSAKSI, 
        PJ.PRODUK,
        PJ.TOTALBIAYA,
        PJ.PEGAWAI_ID, 
        PJ.LAYANANHOTEL_ID, 
        PJ.LAYANANSALON_ID, 
        PJ.LAYANANMEDIS_ID, 
        PJ.PEMILIKHEWAN_ID,
        PJ.onDelete
    FROM Penjualan PJ
    WHERE PJ.ID = p_ID;
END;
/ 

-- Procedure to Create Penjualan
CREATE OR REPLACE PROCEDURE CreatePenjualan(
    p_tanggal IN TIMESTAMP,
    p_produk IN ARRAYPRODUK,
    p_pegawai_id IN VARCHAR2,
    p_layananhotel_id IN VARCHAR2 DEFAULT NULL,
    p_layanansalon_id IN VARCHAR2 DEFAULT NULL,
    p_layananmedis_id IN VARCHAR2 DEFAULT NULL,
    p_pemilikhewan_id IN VARCHAR2 DEFAULT NULL
) AS
    v_total_biaya NUMBER := 0;
    v_harga NUMBER;
BEGIN
    -- Calculate total cost from products
    IF p_produk IS NOT NULL AND p_produk.COUNT > 0 THEN
        FOR i IN 1..p_produk.COUNT LOOP
            -- Get product price
            SELECT HARGA INTO v_harga
            FROM Produk
            WHERE ID = p_produk(i) AND onDelete = 0;
            
            v_total_biaya := v_total_biaya + v_harga;
        END LOOP;
    END IF;

    -- Insert into Penjualan table
    INSERT INTO Penjualan (
        TANGGALTRANSAKSI,
        PRODUK,
        TOTALBIAYA,
        PEGAWAI_ID,
        LAYANANHOTEL_ID,
        LAYANANSALON_ID,
        LAYANANMEDIS_ID,
        PEMILIKHEWAN_ID
    ) VALUES (
        p_tanggal,
        p_produk,
        v_total_biaya,
        p_pegawai_id,
        p_layananhotel_id,
        p_layanansalon_id,
        p_layananmedis_id,
        p_pemilikhewan_id
    );
END;
/ 

CREATE OR REPLACE PROCEDURE UpdatePenjualan(
    p_id IN NUMBER,
    p_customer_id IN NUMBER,
    p_product_list IN VARCHAR2,
    p_total_cost IN NUMBER
)
IS
    v_product_array ARRAYPRODUK := ARRAYPRODUK();
    v_product_id NUMBER;
    v_pos NUMBER;
    v_string VARCHAR2(4000);
BEGIN
    -- Convert comma-separated string to VARRAY
    v_string := p_product_list;
    LOOP
        v_pos := INSTR(v_string, ',');
        IF v_pos = 0 THEN
            -- Last or only item
            v_product_id := TO_NUMBER(v_string);
            v_product_array.EXTEND;
            v_product_array(v_product_array.COUNT) := v_product_id;
            EXIT;
        END IF;
        
        -- Get next item
        v_product_id := TO_NUMBER(SUBSTR(v_string, 1, v_pos-1));
        v_product_array.EXTEND;
        v_product_array(v_product_array.COUNT) := v_product_id;
        
        -- Remove processed item and comma
        v_string := SUBSTR(v_string, v_pos+1);
    END LOOP;

    -- Update transaction
    UPDATE PENJUALAN 
    SET PEMILIKHEWAN_ID = p_customer_id,
        PRODUK = v_product_array,
        TOTALBIAYA = p_total_cost,
        UPDATEDAT = CURRENT_TIMESTAMP
    WHERE ID = p_id;
    
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END UpdatePenjualan;
/ 